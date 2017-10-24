<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Shipping;

use TNW\Subscriptions\Block\Subscription\Summary\BaseSummary;

/**
 *  Class for subscription profile summary shipping details block on frontend Customer Account.
 *
 * @method \TNW\Subscriptions\Model\SubscriptionProfile getSubscriptionProfile()
 */
class Details extends BaseSummary
{
    /**
     * @return string
     */
    public function getShippingDescription()
    {
        return $this->getSubscriptionProfile()->getShippingDescription();
    }

    /**
     * @return string
     */
    public function getEditUrl()
    {
        return $this->getUrl(
            'tnw_subscriptions/subscription/shipment',
            [
                'entity_id' => $this->getSubscriptionProfile()->getId()
            ]
        );
    }
}
