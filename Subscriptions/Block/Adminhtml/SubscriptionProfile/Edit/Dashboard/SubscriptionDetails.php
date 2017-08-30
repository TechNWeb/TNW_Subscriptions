<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Dashboard;

use Magento\Backend\Block\Template;
use Magento\Directory\Model\Currency;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Help render subscription profile details tab.
 */
class SubscriptionDetails extends Template
{
    const ANNUAL = 'Annual Value';//Subscription profile has not duration(infinite).
    const TOTAL = 'Total Value';// Subscription profile has duration.

    /**
     * Subscription profile instance holder.
     *
     * @var SubscriptionProfile
     */
    private $subscriptionProfile;

    /**
     * Help render price for current subscription profile.
     *
     * @var Currency
     */
    private $currency;

    /**
     * SubscriptionDetails constructor.
     *
     * @param Template\Context $context
     * @param Registry $registry
     * @param Currency $currency
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        Currency $currency,
        array $data = []
    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/dashboard/subscription_details.phtml');
        $this->currency = $currency;
        if ($this->subscriptionProfile === null) {
            $this->subscriptionProfile = $registry->registry('tnw_subscription_profile');
        }
        parent::__construct($context, $data);
    }

    /**
     * Get sum off all orders for current subscription profile(How much customer paid up to that moment).
     *
     * @return string
     */
    public function getCurrentValue()
    {
        $profileCurrencyCode = $this->subscriptionProfile->getProfileCurrencyCode();
        $this->currency->setCurrencyCode($profileCurrencyCode);

        return $this->currency->format($this->subscriptionProfile->getCurrentValue());
    }

    /**
     * Get sum of all generated non-paid(has no orders) quotes for current subscription profile
     * (How much customer will pay to the end of the year or subscription profile duration).
     *
     * @return string
     */
    public function getValue()
    {
        $profileCurrencyCode = $this->subscriptionProfile->getProfileCurrencyCode();
        $this->currency->setCurrencyCode($profileCurrencyCode);

        return $this->currency->format($this->subscriptionProfile->getTotalValue());
    }

    /**
     * Returns value type (Total, Annual)depending on subscription profile term type(0 = has duration, 1 = infinite).
     *
     * @return string
     */
    public function getValueType()
    {
        return $this->subscriptionProfile->getTerm() ? self::ANNUAL :self::TOTAL;
    }
}
