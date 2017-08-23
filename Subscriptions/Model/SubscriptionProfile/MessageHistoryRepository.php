<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Api\SearchCriteriaInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\SubscriptionProfileMessageHistoryRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory as ResourceProfileMessageHistory;

class MessageHistoryRepository implements SubscriptionProfileMessageHistoryRepositoryInterface
{
    /**
     * @var ResourceProfileMessageHistory
     */
    private $resource;

    /**
     * @var MessageHistoryFactory
     */
    private $messageHistoryFactory;

    /**
     * AddressRepository constructor.
     * @param ResourceProfileMessageHistory $resource
     * @param MessageHistoryFactory $messageHistoryFactory
     */
    public function __construct(
        ResourceProfileMessageHistory $resource,
        MessageHistoryFactory $messageHistoryFactory
    ) {
        $this->resource = $resource;
        $this->messageHistoryFactory = $messageHistoryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        SubscriptionProfileMessageHistoryInterface $messageHistory
    ) {
        try {
            $this->resource->save($messageHistory);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not save the subscription profile Message history: %1',
                $e->getMessage()
            ));
        }

        return $messageHistory;
    }

    /**
     * * {@inheritdoc}
     */
    public function getById($id)
    {
        /** @var MessageHistory $messageHistory */
        $messageHistory = $this->messageHistoryFactory->create();
        $messageHistory->load($id);

        if (!$messageHistory->getId()) {
            throw new NoSuchEntityException(
                __('Subscription profile Message history with id "%1" does not exist.', $messageHistory)
            );
        }

        return $messageHistory;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ) {
        //todo
    }

    /**
     * {@inheritdoc}
     */
    public function delete(SubscriptionProfileMessageHistoryInterface $messageHistory)
    {
        //todo
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        //todo
    }
}
