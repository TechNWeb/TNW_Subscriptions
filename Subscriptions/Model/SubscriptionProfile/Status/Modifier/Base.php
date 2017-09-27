<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ResourceConnection;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\ProcessInterface;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Base class for status modifiers.
 */
abstract class Base implements ProcessInterface
{
    /**
     * Config model.
     *
     * @var Config
     */
    protected $config;

    /**
     * Context.
     *
     * @var Context
     */
    protected $context;

    /**
     * Resource connection.
     *
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * Repository for retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;
    /**
     * Profile status data source
     *
     * @var ProfileStatus
     */
    private $statusSource;

    /**
     * Message history logger
     *
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * Base constructor.
     * @param Config $config
     * @param Context $context
     * @param ResourceConnection $resource
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param SubscriptionProfileRepository $profileRepository
     * @param ProfileStatus $profileStatus
     * @param MessageHistoryLogger $messageHistoryLogger
     */
    public function __construct(
        Config $config,
        Context $context,
        ResourceConnection $resource,
        SearchCriteriaBuilder $criteriaBuilder,
        SubscriptionProfileRepository $profileRepository,
        ProfileStatus $profileStatus,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->config = $config;
        $this->context = $context;
        $this->resource = $resource;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->profileRepository = $profileRepository;
        $this->statusSource = $profileStatus;
        $this->messageHistoryLogger = $messageHistoryLogger;
    }

    /**
     * Updates profile statuses.
     *
     * @param array $allIds
     */
    public function process(array $allIds)
    {
        $ids = $this->getIdsToModify($allIds);
        $this->criteriaBuilder->addFilter(
            SubscriptionProfileInterface::ID,
            $ids,
            'in'
        );
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->create();
        $profiles = $this->profileRepository->getList($searchCriteria)->getItems();
        /** @var SubscriptionProfileInterface $profile */
        foreach ($profiles as $profile) {
            if ((int)$this->getNewStatus() !== (int)$profile->getStatus()) {
                $oldStatus = $profile->getStatus();
                //change profile status
                $profile->setStatus($this->getNewStatus());
                $profile = $this->profileRepository->save($profile);
                //add comment about status change
                $this->addChangeStatusToHistory(
                    $profile->getId(),
                    (int)$oldStatus,
                    (int)$profile->getStatus()
                );
            }
        }
    }

    /**
     * Returns list of profile ids to modify.
     *
     * @param array $allIds
     * @return array
     */
    abstract protected function getIdsToModify(array $allIds);

    /**
     * Returns new profile status.
     *
     * @return int
     */
    abstract protected function getNewStatus();

    /**
     * Returns list of ignored statuses.
     *
     * @return array
     */
    abstract protected function getIgnoredStatuses();

    /**
     * Adds comment about profile status change.
     *
     * @param int|string $profileId
     * @param int $oldStatus
     * @param int $newStatus
     * @return void
     */
    protected function addChangeStatusToHistory($profileId, $oldStatus, $newStatus)
    {
        $message = sprintf(
            $this->messageHistoryLogger->getMessage(
                MessageHistoryLogger::MESSAGE_SUBSCRIPTION_STATUS_CHANGED),
            $this->statusSource->getLabelByValue($oldStatus),
            $this->statusSource->getLabelByValue($newStatus)
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