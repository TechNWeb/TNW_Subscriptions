<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Product;

use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;

/**
 * Plugin for \Magento\Catalog\Model\Product\Copier.
 *
 * Purpose: process subscription options on product duplication.
 */
class Copier
{
    /**
     * Duplicate product subscription option values.
     *
     * @param \Magento\Catalog\Model\Product\Copier $copier
     * @param \Magento\Catalog\Model\Product $result
     * @return \Magento\Catalog\Model\Product
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCopy(
        \Magento\Catalog\Model\Product\Copier $copier,
        \Magento\Catalog\Model\Product $result
    ) {
        $this->duplicateSubscriptionOptions($result);

        return $result;
    }

    /**
     * Duplicate product subscription options.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    private function duplicateSubscriptionOptions(
        \Magento\Catalog\Model\Product $product
    ) {
        $optionsTable = $product->getResource()->getTable(
            ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE
        );

        $select = $product->getResource()->getConnection()->select()
            ->from($optionsTable, '')
            ->where('magento_product_id = ?', $product->getOriginalId());
        $insertColumns = [
            'billing_frequency_id'      => 'billing_frequency_id',
            'magento_product_id'        => new \Zend_Db_Expr($product->getEntityId()),
            'default_billing_frequency' => 'default_billing_frequency',
            'price'                     => 'price',
            'initial_fee'               => 'initial_fee',
            'sort_order'                => 'sort_order',
            'preset_qty'                => 'preset_qty',
        ];
        $select->columns($insertColumns);
        $query = $select->insertFromSelect($optionsTable, array_keys($insertColumns));
        $product->getResource()->getConnection()->query($query);
    }
}
