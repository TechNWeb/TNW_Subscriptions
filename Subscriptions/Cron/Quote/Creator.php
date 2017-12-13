<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron\Quote;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\CollectionFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class Creator
 */
class Creator extends Base
{
    /**
     * Factory for creating quotes.
     *
     * @var QuoteFactory
     */
    private $quoteFactory;

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
     * Creator constructor.
     * @param SubscriptionProfileRepository $profileRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param Context $context
     * @param Config $config
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteFactory $quoteFactory
     * @param CollectionFactory $collectionFactory
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
        CollectionFactory $collectionFactory,
        RelationManager $relationManager,
        Manager $queueManager,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->queueManager = $queueManager;
        $this->messageHistoryLogger = $messageHistoryLogger;
        parent::__construct($profileRepository, $criteriaBuilder, $context, $config, $cartRepository,
            $collectionFactory, $relationManager
        );
    }

    /**
     * Generates future quotes for profiles and adds them to queue.
     *
     * @param array $data
     */
    public function process(array $data)
    {
        foreach ($data as $websiteId) {
            foreach ($this->getProfiles($websiteId) as $profile) {
                $this->generateProfileQuotes($profile);
            }
        }
    }

    /**
     * Generates future quotes for profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @param null|int $quotesCount
     */
    public function generateProfileQuotes(SubscriptionProfileInterface $profile, $quotesCount = null)
    {
        $relations = [];
        try {
            $count = $quotesCount ?: $this->config->getGeneratedQuotesCount();
            list($cycles, $needMore) = $this->getBillingCycles($profile, $count);
            foreach ($cycles as $cycleDate) {
                $quote = $this->processQuote($profile, $this->getEmptyQuote());
                $relations[] = $this->assignQuoteToProfile(
                    $profile,
                    $quote,
                    $cycleDate
                );
            }
            $this->updateGenerateQuotesState(
                $profile,
                $this->getNeedGenerateState($profile, $needMore)
            );
            //Add created relations to profile process queue
            $this->queueManager->insertItems($relations);
        } catch (\Exception $e) {
            $this->context->log('Error on quotes generation for profile - ' . $profile->getId());
            $this->context->log($e->getMessage());
            $this->updateGenerateQuotesState(
                $profile,
                SubscriptionProfileInterface::GENERATE_QUOTES_STATE_NEED_GENERATE
            );
        }
        $this->profileRepository->save($profile);
    }

    /**
     * @inheritdoc
     */
    public function getProfilesIdsToProcess($websiteId)
    {
        $collection = $this->getBaseCollection()
            ->addFieldToFilter(
                SubscriptionProfileInterface::GENERATE_QUOTES_STATE,
                [
                    'in' => [
                        SubscriptionProfileInterface::GENERATE_QUOTES_STATE_NEED_GENERATE,
                        SubscriptionProfileInterface::GENERATE_QUOTES_STATE_GENERATED_FOR_YEAR
                    ]
                ]
            )
            ->addFieldToFilter(SubscriptionProfileInterface::WEBSITE_ID, $websiteId)
            ->addFieldToFilter(SubscriptionProfileInterface::NEED_RECOLLECT, 0)
            ->addFieldToFilter('products_need_recollect', 0);

        return $collection->getAllIds();
    }

    /**
     * Returns list of billing cycle dates and flag to generate more quotes.
     *
     * @param SubscriptionProfileInterface $profile
     * @param int $count
     * @return array
     * @throws \Exception
     */
    private function getBillingCycles(SubscriptionProfileInterface $profile, $count)
    {
        $neededDates = [];
        $nowDate = new \DateTime();
        $formattedNowDate = $this->format($nowDate);
        $startDate = new \DateTime($profile->getStartDate());
        $formattedStartDate = $this->format($startDate);
        //Add to list start date.
        $neededDates[] = $formattedStartDate;
        //Profile has a infinite count of cycles
        if ($profile->getTerm()) {
            //Generate quotes for the year ahead
            $endDate = new \DateTime($formattedNowDate);
            if (strtotime($formattedStartDate) > strtotime($formattedNowDate)) {
                $endDate = new \DateTime($formattedStartDate);
            }
            $endDate->add(new \DateInterval('P1Y'));
            $dateDiff = $startDate->diff($endDate, true);
            switch ($profile->getUnit()) {
                case BillingFrequencyUnitType::DAYS:
                    $cyclesCount = floor($dateDiff->days / $profile->getFrequency());
                    break;
                case BillingFrequencyUnitType::MONTHS:
                    $months = $dateDiff->y * 12 + $dateDiff->m;
                    $cyclesCount = floor($months / $profile->getFrequency());
                    break;
                default:
                    throw new \Exception('Undefined length unit type.');
                    break;
            }
        } else {
            // Profile has a finite count of cycles
            $cyclesCount = (int)$profile->getTotalBillingCycles() - 1;
        }
        //Calculate the list of dates for profile
        for ($i = 1; $i <= $cyclesCount; $i++) {
            $date = $this->calculateScheduledDate(
                $startDate,
                $profile->getUnit(),
                $profile->getFrequency()
            );
            $neededDates[] = $this->format($date);
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
        $resultDates = array_filter(
            $neededDates,
            function ($neededDate) use ($formattedNowDate) {
                return (strtotime($neededDate) > strtotime($formattedNowDate));
            }
        );
        $resultDates = array_slice($resultDates, 0, $count);
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

    /**
     * Returns empty quote object.
     *
     * @return Quote
     */
    private function getEmptyQuote()
    {
        return $this->quoteFactory->create();
    }

    /**
     * Setting current state to subscription profile attribute.
     *
     * @param SubscriptionProfileInterface $profile
     * @param int $state
     */
    private function updateGenerateQuotesState(SubscriptionProfileInterface $profile, $state)
    {
        $profile->setGenerateQuotesState($state);
    }

    /**
     * Returns need generate state for profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @param $needMore
     * @return int
     */
    private function getNeedGenerateState(SubscriptionProfileInterface $profile, $needMore)
    {
        $needGenerate = $profile->getTerm()
            ? SubscriptionProfileInterface::GENERATE_QUOTES_STATE_GENERATED_FOR_YEAR
            : SubscriptionProfileInterface::GENERATE_QUOTES_STATE_GENERATED;
        $needGenerate = $needMore
            ? SubscriptionProfileInterface::GENERATE_QUOTES_STATE_NEED_GENERATE
            : $needGenerate;

        return $needGenerate;
    }

    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        return [];
    }

    /**
     * Returns formatted date.
     *
     * @param \DateTime $date
     * @return string
     */
    private function format(\DateTime $date)
    {
        return $date->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }
}
