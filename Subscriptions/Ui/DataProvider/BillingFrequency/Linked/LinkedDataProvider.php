<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Linked;

use Magento\Catalog\Ui\DataProvider\Product\Related\AbstractDataProvider;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\Discount;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\LockPrice;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\UnlockPresetQty;

/**
 * Class LinkedDataProvider
 */
class LinkedDataProvider extends AbstractDataProvider
{
    /**
     * Was Product Billing Frequency table joined to collection or not.
     *
     * @var bool
     */
    private $tablesJoined = false;

    /**
     * {@inheritdoc}
     */
    protected function getLinkType()
    {
        return 'linked';
    }

    /**
     * @inheritdoc
     */
    public function getCollection()
    {
        $collection = parent::getCollection();
        $collection->addAttributeToFilter(
            'tnw_subscr_purchase_type',
            [
                'in' => [
                    PurchaseType::RECURRING_PURCHASE_TYPE,
                    PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE,
                ],
            ]
        );

        $collection->addAttributeToSelect([
            UnlockPresetQty::CODE_UNLOCK_PRESET_QTY,
            LockPrice::CODE_LOCK_PRICE,
            LockPrice::CODE_FLAT_DISCOUNT,
            Discount::CODE_DISCOUNT_AMOUNT,
            Discount::CODE_DISCOUNT_TYPE,
        ]);

        if (!$this->tablesJoined) {
            $this->joinTables($collection);

            $this->tablesJoined = true;
        }

        return $collection;
    }

    /**
     * Join table(s) to collection.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     */
    private function joinTables(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection)
    {
        $collection->joinTable(
            $collection->getTable(ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE),
            'magento_product_id=entity_id',
            [
                ProductBillingFrequencyInterface::INITIAL_FEE,
                ProductBillingFrequencyInterface::PRESET_QTY,
                'tnw_' . ProductBillingFrequencyInterface::PRICE => ProductBillingFrequencyInterface::PRICE,
            ]
        );
    }
}
