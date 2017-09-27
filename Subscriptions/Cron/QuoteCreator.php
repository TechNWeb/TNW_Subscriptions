<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class QuoteCreator
 */
class QuoteCreator
{
    /**
     * Repository for retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * Subscriptions context.
     *
     * @var Context
     */
    private $context;

    /**
     * Subscriptions config.
     *
     * @var Config
     */
    private $config;

    /**
     * Repository fore saving/retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * Factory for creating quotes.
     *
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * Profile relation manager.
     *
     * @var RelationManager
     */
    private $relationManager;

    /**
     * Profile process queue manager.
     *
     * @var Manager
     */
    private $queueManager;

    /**
     * Message history logger.
     *
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * QuoteCreator constructor.
     * @param SubscriptionProfileRepository $profileRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param Context $context
     * @param Config $config
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteFactory $quoteFactory
     * @param RelationManager $relationManager
     * @param Manager $queueManager
     * @param MessageHistoryLogger $messageHistoryLogger
     */
    public function __construct(
        SubscriptionProfileRepository $profileRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        Context $context,
        Config $config,
        CartRepositoryInterface $cartRepository,
        QuoteFactory $quoteFactory,
        RelationManager $relationManager,
        Manager $queueManager,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->profileRepository = $profileRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->context = $context;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
        $this->quoteFactory = $quoteFactory;
        $this->relationManager = $relationManager;
        $this->queueManager = $queueManager;
        $this->messageHistoryLogger = $messageHistoryLogger;
    }

    /**
     * Generates future quotes for profile and adds them to queue.
     *
     * @param int $websiteId
     */
    public function process($websiteId)
    {
        $relations = [];
        foreach ($this->getProfiles($websiteId) as $profile) {
            try {
                list($cycles, $needMore) = $this->getBillingCycles($profile);
                foreach ($cycles as $cycleDate) {
                    $quote = $this->generateQuote($profile);
                    $relations[] = $this->assignQuoteToProfile(
                        $profile,
                        $quote,
                        $cycleDate
                    );
                }

                if ($profile->getTerm()) {
                    $profile->setNeedGenerateQuotes(2);
                    if ($needMore) {
                        $profile->setNeedGenerateQuotes(1);
                    }
                } else {
                    $profile->setNeedGenerateQuotes(0);
                    if ($needMore) {
                        $profile->setNeedGenerateQuotes(1);
                    }
                }
            } catch (\Exception $e) {
                $this->context->log('Error on quotes generation for profile - ' . $profile->getId());
                $this->context->log($e->getMessage());
                $profile->setNeedGenerateQuotes(1);
            }
            $this->profileRepository->save($profile);
        }
        //Add created relations to profile process queue
        $this->queueManager->insertItems($relations);
    }

    /**
     * Returns list of profiles with no quotes.
     *
     * @param int $websiteId
     * @return SubscriptionProfileInterface[]
     */
    private function getProfiles($websiteId)
    {
        $this->criteriaBuilder->addFilter(
            SubscriptionProfileInterface::NEED_GENERATE_QUOTES,
            true
            )->addFilter(
                SubscriptionProfileInterface::WEBSITE_ID,
                $websiteId
            );
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->create();

        return $this->profileRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Returns list of billing cycle dates and flag to generate more quotes.
     *
     * @param SubscriptionProfileInterface $profile
     * @return array
     * @throws \Exception
     */
    private function getBillingCycles(SubscriptionProfileInterface $profile)
    {
        $neededDates = [];
        $date = new \DateTime($profile->getStartDate());
        // If profile has a trial period then add to list start date.
        if ($profile->getStatus() === ProfileStatus::STATUS_TRIAL) {
            $neededDates[] = $date->format('Y-m-d H:i:s');
        }
        //Profile has a infinite count of cycles
        if ($profile->getTerm()) {
            //End date of current year
            $endDate = new \DateTime();
            $endDate->setDate($endDate->format('Y'), 12, 31);
            switch ($profile->getUnit()) {
                case BillingFrequencyUnitType::DAYS:
                    $cyclesCount = floor($date->diff($endDate, true)->days / $profile->getFrequency());
                    break;
                case BillingFrequencyUnitType::MONTHS:
                    $cyclesCount = floor($date->diff($endDate, true)->m / $profile->getFrequency());
                    break;
                default:
                    throw new \Exception('Undefined length unit type.');
                    break;
            }
        } else {
            // Profile has a finite count of cycles
            $cyclesCount = (int)$profile->getTotalBillingCycles();
        }
        //Calculate the list of dates for profile
        for ($i = 1; $i <= $cyclesCount; $i++) {
            $date = $this->calculateScheduledDate(
                $date,
                $profile->getUnit(),
                $profile->getFrequency()
            );
            $neededDates[] = $date->format('Y-m-d H:i:s');
        }
        //get already generated dates
        $existDates = array_map(
            function (SubscriptionProfileOrderInterface $relation) {
                return $relation->getScheduledAt();
            },
            $this->relationManager->getAllProfileRelations($profile->getId())
        );
        $neededDates = array_diff($neededDates, $existDates);
        //generate only future dates
        $nowDate = (new \DateTime())->format('Y-m-d H:i:s');
        $resultDates = array_filter(
            $neededDates,
            function ($neededDate) use ($nowDate) {
                return (strtotime($neededDate) > strtotime($nowDate));
            }
        );
        $resultDates = array_slice($resultDates, 0, $this->config->getGeneratedQuotesCount());
        $needMore = count($neededDates) > count($resultDates);

        return [$resultDates, $needMore];
    }

    /**
     * Calculates subscription date for billing cycle.
     *
     * @param \DateTime $date
     * @param string $unit
     * @param int $length
     * @return \DateTime
     * @throws \Exception
     */
    private function calculateScheduledDate(\DateTime $date, $unit, $length)
    {
        switch ($unit) {
            case BillingFrequencyUnitType::DAYS:
                $intervalUnit = 'D';
                break;
            case BillingFrequencyUnitType::MONTHS:
                $intervalUnit = 'M';
                break;
            default:
                throw new \Exception('Undefined length unit type.');
                break;
        }

        $expression = 'P' . $length . $intervalUnit;

        return $date->add(new \DateInterval($expression));
    }

    /**
     * Returns request for adding product to subscription quote.
     *
     * @param ProductSubscriptionProfileInterface $profileProduct
     * @return DataObject
     */
    private function getProductAddRequest(
        ProductSubscriptionProfileInterface $profileProduct
    ) {
        $data = [
            'custom_price' => $profileProduct->getPrice(),
            'qty' => $profileProduct->getQty()
        ];

        return new DataObject($data);
    }

    /**
     * Creates relation between profile and scheduled quote.
     *
     * @param SubscriptionProfileInterface $profile
     * @param Quote $quote
     * @param string $date
     * @return int
     */
    private function assignQuoteToProfile(
        SubscriptionProfileInterface $profile,
        Quote $quote,
        $date
    ) {
        $relation = $this->relationManager->getNewProfileOrderRelation()
            ->setSubscriptionProfileId($profile->getId())
            ->setMagentoQuoteId($quote->getId())
            ->setScheduledAt($date);
        $id = $this->relationManager->saveRelation($relation)->getId();

        $this->logToMessageHistory($relation, $profile->getId());

        return $id;
    }

    /**
     * Creates quote for profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @return mixed
     */
    private function generateQuote(SubscriptionProfileInterface $profile)
    {
        /** @var Quote $quote */
        $quote = $this->quoteFactory->create();
        //Deactivate quote
        $quote->setIsActive(false);
        $quote->setData('ignore_old_qty', true);
        $quote->setData('is_super_mode', true);
        //Set store
        $quote->setStore(
            $profile->getWebsite()->getDefaultStore()
        );
        //Set currency
        $quote->setQuoteCurrencyCode($profile->getProfileCurrencyCode());
        //Set customer
        $quote->assignCustomer($profile->getCustomer());
        //Add products
        foreach ($profile->getProducts() as $profileProduct) {
            $addRequest = $this->getProductAddRequest(
                $profileProduct
            );
            $quote->addProduct(
                $profileProduct->getMagentoProduct(),
                $addRequest
            );
        }
        //Set shipping address
        $quote->getShippingAddress()->addData(
            $profile->getShippingAddress()->getData()
        );
        $quote->getShippingAddress()->setCustomerId(
            $profile->getCustomerId()
        );
        //Set billing address
        $quote->getBillingAddress()->addData(
            $profile->getBillingAddress()->getData()
        );
        $quote->getBillingAddress()->setCustomerId(
            $profile->getCustomerId()
        );
        //Set shipping method
        $quote->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($profile->getShippingMethod());
        $quote->setTotalsCollectedFlag(false);
        $this->cartRepository->save($quote);

        return $quote;
    }

    /**
     * Log message for Subscription Profile message history.
     *
     * @param SubscriptionProfileOrderInterface $relation
     * @param $profileId
     *
     * @return void
     */
    private function logToMessageHistory(SubscriptionProfileOrderInterface $relation, $profileId)
    {
        $message = sprintf(
            $this->messageHistoryLogger->getMessage(MessageHistoryLogger::MESSAGE_QUOTE_CREATED),
            $this->messageHistoryLogger->getConvertedQuoteId($relation->getMagentoQuoteId()),
            $relation->getScheduledAt()
        );

        $this->messageHistoryLogger->log(
            $message,
            $profileId,
            false,
            false,
            true
        );
    }
}
