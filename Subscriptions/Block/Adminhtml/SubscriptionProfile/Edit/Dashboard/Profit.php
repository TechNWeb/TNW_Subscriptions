<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Dashboard;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Help render profit tab information for Subscription Profile admin dashboard.
 */
class Profit extends Template
{
    /**
     * @var SubscriptionProfile
     */
    private $subscriptionProfile;

    /**
     * Calculate different profit values for given subscription profile.
     *
     * @var SubscriptionProfile\ProfitCalculator
     */
    private $profitCalculator;

    /**
     * Profit constructor.
     *
     * @param Template\Context $context
     * @param Registry $registry
     * @param SubscriptionProfile\ProfitCalculator $profitCalculator
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        SubscriptionProfile\ProfitCalculator $profitCalculator,
        array $data = []
    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/dashboard/profit.phtml');
        if ($this->subscriptionProfile === null) {
            $this->subscriptionProfile = $registry->registry('tnw_subscription_profile');
        }
        $this->profitCalculator = $profitCalculator;
        parent::__construct($context, $data);
    }

    /**
     * Get actual profit for today for given subscription profile.
     * As of today profit equals (product price - product cost) * product amount from paid quotes(has order).
     *
     * @return string
     */
    public function getAsOfTodayProfit()
    {
        return $this->profitCalculator->getRenderedAsOfTodayProfit($this->subscriptionProfile);
    }

    /**
     * Get potential profit for given subscription profile.
     * Remaining profit equals (product price - product cost) * product amount form non paid quotes(has no order).
     *
     * @return string
     */
    public function getRemainingProfit()
    {
        return $this->profitCalculator->getRenderedRemainingProfit($this->subscriptionProfile);
    }
}
