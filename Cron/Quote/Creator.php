<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManagerFactory
     */
    private $billingCyclesManagerFactory;

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
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManagerFactory $billingCyclesManagerFactory
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
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager,
        \TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManagerFactory $billingCyclesManagerFactory,
        Manager $queueManager,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->billingCyclesManagerFactory = $billingCyclesManagerFactory;
        $this->quoteFactory = $quoteFactory;
        $this->queueManager = $queueManager;
        $this->messageHistoryLogger = $messageHistoryLogger;
        parent::__construct(
            $profileRepository,
            $criteriaBuilder,
            $context,
            $config,
            $cartRepository,
            $collectionFactory,
            $relationManager,
            $profileManager
        );
    }

    /**
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(array $data)
    {
        $this->context->messageDebug('Process quote creator');
        foreach ($data as $websiteId) {
            foreach ($this->getProfiles($websiteId) as $profile) {
                try {
                    $this->context->messageDebug('Profile #%s', $profile->getId());

                    $this->generateProfileQuotes($profile);
                } catch (\Exception $e) {
                    $this->context->messageError('Quote creator error. %s', $e->getMessage());
                }
            }
        }
    }

    /**
     * @param SubscriptionProfileInterface $profile
     * @return \Magento\Quote\Api\Data\CartInterface|Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    public function generateProfileQuote(SubscriptionProfileInterface $profile)
    {
        // Create empty cart
        $quote = $this->getEmptyQuote($profile);
        $this->cartRepository->save($quote);

        // Fill cart
        $quote = $this->cartRepository->get($quote->getId());
        $quote = $this->processQuote($profile, $quote);

        $this->context->messageDebug(
            "Created Quote . Data Quote:\n%s\nData Quote Items:\n%s",
            $quote,
            $quote->getItemsCollection()
        );

        return $quote;
    }

    /**
     * Generates future quotes for profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @param int $count
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function generateProfileQuotes(SubscriptionProfileInterface $profile, $count = 1)
    {
        $relations = [];
        try {
            list($cycles, $needMore) = $this->billingCyclesManagerFactory->create()->getBillingCycles($profile, $count);

            if (!empty($cycles)) {
                $this->context->messageDebug("Create Quotes by Profile:\n%s", $profile);
                $this->context->messageDebug("Generate cycles date:\n%s", $cycles);
            }

            foreach ($cycles as $cycleDate) {
                $quote = $this->generateProfileQuote($profile);
                $relations[] = $this->assignQuoteToProfile(
                    $profile,
                    $quote,
                    $cycleDate
                );
                $this->profileManager->populateTotals($profile, $quote);
            }

            $this->updateGenerateQuotesState(
                $profile,
                $this->getNeedGenerateState($profile, $needMore)
            );

            //Add created relations to profile process queue
            $this->queueManager->insertItems($relations);
        } catch (\Exception $e) {
            $this->context->messageError(
                'Error on quotes generation for profile - %d. Message: %s',
                $profile->getId(),
                $e
            );

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
            ->addFieldToFilter(SubscriptionProfileInterface::GENERATE_QUOTES_STATE, ['in' => [
                SubscriptionProfileInterface::GENERATE_QUOTES_STATE_NEED_GENERATE,
                SubscriptionProfileInterface::GENERATE_QUOTES_STATE_GENERATED_FOR_YEAR
            ]])
            ->addFieldToFilter(SubscriptionProfileInterface::STATUS, ['nin' => [
                \TNW\Subscriptions\Model\Source\ProfileStatus::STATUS_COMPLETE,
                \TNW\Subscriptions\Model\Source\ProfileStatus::STATUS_CANCELED
            ]])
            ->addFieldToFilter(SubscriptionProfileInterface::WEBSITE_ID, $websiteId)
            ->addFieldToFilter(SubscriptionProfileInterface::NEED_RECOLLECT, 0)
            ->addFieldToFilter('products_need_recollect', 0);

        return $collection->getAllIds();
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

        $quote->setData('scheduled', true);
        $quote->setData('scheduled_at', $date);
        $this->cartRepository->save($quote);

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
     * @param SubscriptionProfileInterface $profile
     *
     * @return Quote
     */
    private function getEmptyQuote(SubscriptionProfileInterface $profile)
    {
        return $this->quoteFactory->create(['data' => ['is_active' => false]])
            ->assignCustomer($profile->getCustomer());
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
}
