<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Shipping;

use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Block\Subscription\Summary\BaseSummary;

/**
 *  Class for subscription profile summary shipping details block on frontend Customer Account.
 */
class Details extends BaseSummary
{
    /**
     * @param Template\Context $context
     */
    public function __construct(
        Template\Context $context
    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/summary/overview/shipping-details.phtml');
        parent::__construct($context);
    }

    /**
     * Return current profile shipping method description
     *
     * @return string|null
     */
    public function getShippingDescription()
    {
        return $this->getSubscriptionProfile()
            ? $this->getSubscriptionProfile()->getShippingDescription()
            : '';
    }

    /**
     * @inheritdoc
     */
    public function getEditUrl($tabName = 'shipment', array $params = [])
    {
        $params['shipping_details'] = 1;

        return parent::getEditUrl($tabName, $params);
    }

    /**
     * Return Tab name.
     *
     * @return string
     */
    public function getTabName()
    {
        return 'shipping';
    }

    /**
     * Check if only table should be rendered
     *
     * @return bool|null
     */
    public function isOnlyTableContent()
    {
        return $this->getOnlyTableContent();
    }
}
