<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Product;

use Magento\Catalog\Model\Indexer\Product\Price\Action\Full;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\DataObject;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency;
use TNW\Subscriptions\Model\Product\Attribute;

class UpdateImportedBillingFrequency
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Action
     */
    private $action;

    /**
     * @var ProductBillingFrequency
     */
    private $productBillingFrequency;

    /**
     * UpdateImportedBillingFrequency constructor.
     * @param CollectionFactory $productCollectionFactory
     * @param Action $action
     * @param ProductBillingFrequency $productBillingFrequency
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        Action $action,
        ProductBillingFrequency $productBillingFrequency
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->action = $action;
        $this->productBillingFrequency = $productBillingFrequency;
    }

    /**
     * @param Full $full
     * @param $result
     * @return mixed
     */
    public function afterExecute(Full $full, $result)
    {
        $collection = $this->getProductCollection();
        foreach ($collection as $item) {
            if ($item[Attribute::SUBSCRIPTION_BILLING_FREQUENCY_USED] != 1) {
                $productId = $item->getEntityId();
                $this->productBillingFrequency->setImportedBillingFrequency(
                    $item[Attribute::SUBSCRIPTION_BILLING_FREQUENCY],
                    $productId
                );
                $this->action->updateAttributes(
                    [$item['entity_id']],
                    [Attribute::SUBSCRIPTION_BILLING_FREQUENCY_USED => 1],
                    $item->getStoreId()
                );
            }
        }

        return $result;
    }

    /**
     * Get filtered product collection
     *
     * @return DataObject[]
     */
    public function getProductCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(
            Attribute::SUBSCRIPTION_BILLING_FREQUENCY
        );
        $collection->addAttributeToSelect(
            Attribute::SUBSCRIPTION_BILLING_FREQUENCY_USED
        );
        $collection->addFieldToFilter(
            Attribute::SUBSCRIPTION_BILLING_FREQUENCY_USED, ['eq' => [0]]
        );
        return $collection->getItems();
    }
}
