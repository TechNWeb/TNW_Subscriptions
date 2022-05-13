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
use Magento\AdminGws\Model\Collections;

/**
 * Class queue listing data provider
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var Collections
     */
    private $collectionRoleRestrictor;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param Collections $collectionRoleRestrictor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Collections $collectionRoleRestrictor,
        array $meta = [],
        array $data = []
    ) {
        $this->collectionRoleRestrictor = $collectionRoleRestrictor;
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
            'main_table.' . SubscriptionProfileQueueInterface::PROFILE_ORDER_ID . '= relation.'
            . SubscriptionProfileOrderInterface::ID,
            [
                SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID,
                SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID,
                SubscriptionProfileOrderInterface::SCHEDULED_AT
            ]
        )->joinLeft(
            [
            'subscription_profile' => $collection->getTable('tnw_subscriptions_subscription_profile_entity')
            ],
            'subscription_profile.entity_id = relation.subscription_profile_id',
            [
                'subscription_profile.store_id',
                'subscription_profile.website_id'
            ]
        );
        $this->collectionRoleRestrictor->addStoreFilter($collection);
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
