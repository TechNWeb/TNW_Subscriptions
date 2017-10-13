<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Shipping;

use Magento\Framework\View\Element\Template;

/**
 * Class Shipment Details
 *
 * @method \TNW\Subscriptions\Model\SubscriptionProfile getSubscriptionProfile()
 */
class Details extends Template
{
    /**
     * @return string
     */
    public function getShippingDescription()
    {
        return $this->getSubscriptionProfile()->getShippingDescription();
    }
}