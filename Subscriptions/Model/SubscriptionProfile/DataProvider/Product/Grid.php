<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;


class Grid extends ProductDataProvider
{
    /**#@+
     * Modal products grid data scope
     */
    const DATA_SCOPE_ADD_PRODUCT_GRID = 'tnw_subscriptionprofile_create_add_product_modal_listing';
    /**#@-*/
    /**
     * Grid constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $addFieldStrategies
     * @param array $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        $addFieldStrategies = [],
        $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $collectionFactory,
            $addFieldStrategies, $addFilterStrategies, $meta, $data
        );
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()
                ->getSelect()
                ->join(
                    ['sub_table' => ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE],
                    'e.entity_id = sub_table.' . ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
                    []
                )
                ->join(
                    ['sub_frequency_table' => BillingFrequencyInterface::SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE],
                    'sub_frequency_table.' . BillingFrequencyInterface::ID.' = sub_table.'
                    . ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID
                    . ' AND sub_frequency_table.'. BillingFrequencyInterface::STATUS .' = 1',
                    []
                )
                ->group('e.entity_id');

            $this->getCollection()->load();
        }
        $items = $this->getCollection()->toArray();

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items),
        ];
    }
}
