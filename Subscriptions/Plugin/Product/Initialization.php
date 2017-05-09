<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Plugin\Product;

use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterfaceFactory;

class Initialization
{
    /**
     * @var ProductBillingFrequencyInterfaceFactory
     */
    private $productBillingFrequencyInterfaceFactory;

    /**
     * Initialize constructor.
     * @param ProductBillingFrequencyInterfaceFactory $productBillingFrequencyInterfaceFactory
     */
    public function __construct(
        ProductBillingFrequencyInterfaceFactory $productBillingFrequencyInterfaceFactory
    )
    {
        $this->productBillingFrequencyInterfaceFactory = $productBillingFrequencyInterfaceFactory;
    }

    /**
     * Prepare recurring options for product
     *
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $subject
     * @param \Magento\Catalog\Model\Product $product
     * @param array $productData
     *
     * @return array
     */
    public function beforeInitializeFromData(
        \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $subject,
        \Magento\Catalog\Model\Product $product,
        array $productData
    ) {

        if (isset($productData['recurring_options'])) {
            $options = $productData['recurring_options'];
            unset($productData['recurring_options']);
        } else {
            $options = [];
        }

        /**
         * Initialize recurring options
         */
        if ($options) {
            $recurringOptions = [];
            foreach ($options as $recurringOptionData) {
                if (empty($recurringOptionData['is_delete'])) {
                    /** @var ProductBillingFrequencyInterface $recurringOption */
                    $recurringOption = $this->productBillingFrequencyInterfaceFactory->create(['data' => $recurringOptionData]);
                    $recurringOption->setMagentoProductId($product->getId());
                    $recurringOption->setId(null);
                    $recurringOptions[] = $recurringOption;
                }
            }
            $product->setData('recurring_options', $recurringOptions);
        }

        $product->setCanSaveRecurringOptions(
            !empty($productData['affect_product_recurring_options'])
        );


        return [$product, $productData];
    }
}