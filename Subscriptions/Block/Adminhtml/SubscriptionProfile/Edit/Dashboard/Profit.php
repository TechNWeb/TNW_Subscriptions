<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Dashboard;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Help render profit tab information for Subscription Profile admin dashboard.
 */
class Profit extends Template
{
    /**
     * @var Registry
     */
    private $registry;

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
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->profitCalculator = $profitCalculator;
    }

    public function subscriptionProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
    }

    /**
     * Get actual profit for today for given subscription profile.
     * As of today profit equals (product price - product cost) * product amount from paid quotes(has order).
     *
     * @return string
     */
    public function getAsOfTodayProfit()
    {
        return (float)$this->profitCalculator->getAsOfTodayProfit($this->subscriptionProfile());
    }

    /**
     * @return string
     */
    public function getRenderedAsOfTodayProfit($includeContainer = true)
    {
        return $this->profitCalculator
            ->getRenderedAsOfTodayProfit($this->subscriptionProfile(), $includeContainer);
    }

    /**
     * Get potential profit for given subscription profile.
     * Remaining profit equals (product price - product cost) * product amount form non paid quotes(has no order).
     *
     * @return string
     */
    public function getRemainingProfit()
    {
        return (float)$this->profitCalculator->getRemainingProfit($this->subscriptionProfile());
    }

    /**
     * @return float|int
     */
    public function getRenderedRemainingProfit($includeContainer = true)
    {
        return $this->profitCalculator
            ->getRenderedRemainingProfit($this->subscriptionProfile(), $includeContainer);
    }

    /**
     * Get can hide Remaining profit row
     * 
     * @return bool
     */
    public function getHideRemainingProfit()
    {
        return
            (
                $this->subscriptionProfile()->getStatus() == ProfileStatus::STATUS_COMPLETE
                && empty($this->profitCalculator->getRemainingProfit($this->subscriptionProfile()))
            )
            || $this->subscriptionProfile()->getStatus() == ProfileStatus::STATUS_CANCELED;
    }
}
