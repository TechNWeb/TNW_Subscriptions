<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Subscription Email Overview block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailOverview extends \Magento\Framework\View\Element\Template
{
    /**
     * Returns shipping info block html.
     *
     * @return string
     */
    public function getShippingInfoHtml()
    {
        return $this->getChildHtml('shipping-information');
    }

    /**
     * @return string
     */
    public function getShippingDetailsHtml()
    {
        return $this->getChildHtml('shipping-details');
    }

    /**
     * Check if it necessary to show shipping details block.
     *
     * @return bool
     */
    public function canShowShippingDetailsBlock()
    {
        $canShow = false;
        foreach ($this->getSubscriptionProfiles() as $profile) {
            if (!(bool)$profile->getIsVirtual()) {
                $canShow = true;
            }
        }
        return $canShow;
    }

    /**
     * @return SubscriptionProfile[]
     */
    public function getSubscriptionProfiles()
    {
        return $this->getData('subscription_profiles') ?? [$this->getData('subscription_profile')];
    }

    /**
     * Returns billing info block html.
     *
     * @return string
     */
    public function getBillingInfoHtml()
    {
        return $this->getChildHtml('billing-information');
    }

    /**
     * Returns payment details block html.
     *
     * @return string
     */
    public function getPaymentDetailsHtml()
    {
        return $this->getChildHtml('payment-details');
    }

    /**
     * Returns products block html.
     *
     * @return string
     */
    public function getProductsHtml()
    {
        return $this->getChildHtml('products');
    }
}
