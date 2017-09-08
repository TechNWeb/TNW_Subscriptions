<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Queue;

use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Model\ResourceModel\Queue\Collection;
use TNW\Subscriptions\Model\ResourceModel\Queue\CollectionFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileQueueInterface;

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
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        /** @var Collection $collection */
        $collection = $this->getCollection();
        $collection->addFilterToMap('id', 'main_table.id');
        $collection->getSelect()->join(
            ['relation' => SubscriptionProfileOrderInterface::MAIN_TABLE],
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
}