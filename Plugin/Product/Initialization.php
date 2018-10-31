<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Plugin\Product;

use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterfaceFactory;
use TNW\Subscriptions\Model\Product\Attribute;

class Initialization
{
    /**
     * @var ProductBillingFrequencyInterfaceFactory
     */
    private $productBillingFrequencyInterfaceFactory;

    /**
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
     *
     * @return \Magento\Catalog\Model\Product $product
     */
    public function afterInitializeFromData(
        \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $subject,
        \Magento\Catalog\Model\Product $product
    ) {

        if ($product->getData('recurring_options')) {
            $options = $product->getData('recurring_options');
            $product->unsetData('recurring_options');
        } else {
            $options = [];
        }

        /**
         * Initialize recurring options
         */
        if ($options) {
            $recurringOptions = [];
            foreach ($options as $recurringOptionData) {
                $recurringOptionData = $this->processPresetQty($product, $recurringOptionData);
                if (empty($recurringOptionData['is_delete'])) {
                    /** @var ProductBillingFrequencyInterface $recurringOption */
                    $recurringOption = $this->productBillingFrequencyInterfaceFactory->create(['data' => $recurringOptionData]);
                    $recurringOption->setProductSku($product->getSku());
                    $recurringOption->setMagentoProductId($product->getId());
                    $recurringOption->setId(null);
                    $recurringOptions[] = $recurringOption;
                }
            }
            $product->setData('recurring_options', $recurringOptions);
        }

        $product->setCanSaveRecurringOptions(
            !empty($product->getData('affect_product_recurring_options'))
        );


        return $product;
    }

    /**
     * Unset preset qty if disabled.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $recurringOptionData
     * @return array
     */
    private function processPresetQty($product, $recurringOptionData)
    {
        if ((int)$product->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY) === 0) {
            unset($recurringOptionData['preset_qty']);
        }

        return $recurringOptionData;
    }
}
