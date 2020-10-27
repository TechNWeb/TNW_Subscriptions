<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Plugin\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Service\Serializer;

/**
 * Plugin for processing recurring options.
 */
class Product
{

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        Serializer $serializer
    ) {
        $this->serializer = $serializer;
    }
    /**
     * prepare recurring options before product save
     *
     * @param ProductInterface $product
     *
     * @return array
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
                $defaultBillingFrequencyChecked = 0;
                array_filter($options, function($option) use (&$defaultBillingFrequencyChecked) {
                    if ($option->getDefaultBillingFrequency()) {
                        $defaultBillingFrequencyChecked = 1;
                        return;
                    }
                }, ARRAY_FILTER_USE_BOTH);
                if (!$defaultBillingFrequencyChecked && isset($options[0])) {
                    $options[0]->setDefaultBillingFrequency(1);
                }
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
        if (is_array($product->getData('tnw_subscr_inheritance'))) {
            $product->setData(
                'tnw_subscr_inheritance',
                $this->serializer->serialize($product->getData('tnw_subscr_inheritance'))
            );
        }

        return [$product];
    }

    /**
     * @param \Magento\Catalog\Model\Product $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject $buyRequest
     *
     * @return mixed
     */
    public function aroundProcessBuyRequest(
        \Magento\Catalog\Model\Product $subject,
        \Closure $proceed,
        \Magento\Framework\DataObject $buyRequest
    ) {
        return $proceed($buyRequest)
            ->addData(['subscription_data'=>$buyRequest->getSubscriptionData()]);
    }
}
