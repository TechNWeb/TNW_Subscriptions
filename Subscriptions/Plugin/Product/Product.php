<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Plugin\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;

/**
 * Plugin for processing recurring options.
 */
class Product
{
    /**
     * prepare recurring options before product save
     *
     * @param ProductInterface $product
     *
     * @return ProductInterface
     */
    public function beforeSave(ProductInterface $product)
    {
        /**
         * $this->getCanSaveRecurringOptions() - set either in controller when "Recurring Options" ajax tab is loaded,
         * or in type instance as well
         */
        if ($product->getCanSaveRecurringOptions()) {
            $options = $product->getData('recurring_options');
            if (is_array($options)) {
                $product->setIsRecurringOptionChanged(true);
                foreach ($options as $option) {
                    if ($option instanceof ProductBillingFrequencyInterface) {
                        $option = $option->getData();
                    }
                    if (!isset($option['is_delete']) || $option['is_delete'] != '1') {
                        $product->setHasRecurringOptions(true);
                    }
                }
            }
        }

        return [$product];
    }
}
