<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Queue;

use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileQueueInterface;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;
use TNW\Subscriptions\Model\ResourceModel\Queue\Collection;
use TNW\Subscriptions\Model\ResourceModel\Queue\CollectionFactory;

/**
 * Class queue listing data provider
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->addColumnsFiltersToMap();

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        /** @var Collection $collection */
        $collection = $this->getCollection();

        /* hide all such records if they are more than 2 months and status not complete */

        $minDate = date(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT, strtotime("-2 months"));
        $collection->addFieldToFilter(
            ['updated_at', 'status'],
            [
                ['gteq' => $minDate],
                ['neq' => QueueStatus::QUEUE_STATUS_COMPLETE],
            ]
        );

        $collection->getSelect()->join(
            ['relation' => $collection->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
            'main_table.' . SubscriptionProfileQueueInterface::PROFILE_ORDER_ID . '= relation.' . SubscriptionProfileOrderInterface::ID,
            [
                SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID,
                SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID,
                SubscriptionProfileOrderInterface::SCHEDULED_AT
            ]
        )->joinLeft(
            [
                'magento_quote' => $collection->getTable('quote')
            ],
            'magento_quote.entity_id = relation.magento_quote_id',
            [
                'magento_quote.grand_total'
            ]
        );

        return $collection->toArray();
    }

    /**
     * Adds filters to map
     */
    private function addColumnsFiltersToMap()
    {
        /** @var Collection $collection */
        $collection = $this->getCollection();
        $columns = [
            'id',
            'profile_order_id',
            'status',
            'attempt_count',
            'message',
            'created_at',
            'updated_at',
        ];
        foreach ($columns as $column) {
            $collection->addFilterToMap($column, "main_table.$column");
        }
    }
}