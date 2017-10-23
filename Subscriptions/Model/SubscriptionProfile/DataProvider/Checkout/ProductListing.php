<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

/**
 * Class ProductListing data provider
 */
class ProductListing extends Product
{
    /**
     * Listing image id.
     */
    const LISTING_RENDER_URL = 'tnw_subscriptions/ui/render';

    /**
     * Listing image id.
     */
    const LISTING_IMAGE_ID = 'product_thumbnail_image';

    /**
     * Retrieve ui shipping method data.
     *
     * @param ModelQuote $quote
     * @return array
     */
    protected function getShippingMethodData($quote)
    {
        $shippingMethods = [];
        $label = '';
        $needShowAttention = false;
        $this->shippingMethods->setQuote($quote);
        if ($this->shippingMethods->canShowShippingMethodLabel()) {
            $label = __('Selected on next step');
            if ($this->getCurrentCheckoutStep() === self::CHECKOUT_STEP_PAYMENT) {
                $label = $this->shippingMethods->getCurrentMethodLabel();
                $currentShippingMethod = explode("_", $this->shippingMethods->getCurrentShippingMethod());
                if (!in_array($currentShippingMethod[0], $this->shippingMethods->getDontCostDependedMethodsCodes())) {
                    $needShowAttention = true;
                }
            } elseif ($this->getCurrentCheckoutStep() === self::CHECKOUT_STEP_BILLING) {
                $shippingMethods = $this->shippingMethods->getShippingMethodsAsOptionArray();
                $needShowAttention = true;
                $label = '';
            }
        }
        return [
            'label' => $label,
            'methods' => $shippingMethods,
            'needShowAttention' => $needShowAttention,
            'sub_quote_id' => $quote->getId(),
            'value' => $quote->getShippingAddress()->getShippingMethod()
        ];
    }

    /**
     * Retrieve current checkout step.
     *
     * @return string
     */
    private function getCurrentCheckoutStep()
    {
        return $this->request->getParam('currentStep');
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        return [];
    }
}
