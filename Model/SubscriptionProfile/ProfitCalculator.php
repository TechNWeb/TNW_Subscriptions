<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Directory\Model\Currency;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Calculate "total" profit, "remaining" profit and "as of today" profit for given subscription profile.
 */
class ProfitCalculator
{
    /** Profit types. */
    const AS_OF_TODAY = 'as_of_today';
    const REMAINING = 'remaining';

    /**
     * @var array
     */
    private $remainingProfit = [];

    /**
     * @var array
     */
    private $asOfTodayProfit = [];

    /**
     * @var ProductBillingFrequencyRepositoryInterface
     */
    private $recurringOptionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var SubscriptionProfile
     */
    private $subscriptionProfile;

    /**
     * ProfitCalculator constructor.
     *
     * @param ProductBillingFrequencyRepositoryInterface $recurringOptionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Currency $currency
     * @param SubscriptionProfile $subscriptionProfile
     */
    public function __construct(
        ProductBillingFrequencyRepositoryInterface $recurringOptionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Currency $currency,
        SubscriptionProfile $subscriptionProfile
    ) {
        $this->recurringOptionRepository = $recurringOptionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->currency = $currency;
        $this->subscriptionProfile = $subscriptionProfile;
    }

    /**
     * Get total profit for given subscription profile.
     * Total profit equals "as of today" profit + "remaining" profit.
     *
     * @param SubscriptionProfile $subscriptionProfile
     *
     * @return float|int
     * @throws LocalizedException
     */
    public function getTotalProfit(SubscriptionProfile $subscriptionProfile)
    {
        return $this->getAsOfTodayProfit($subscriptionProfile) + $this->getRemainingProfit($subscriptionProfile);
    }

    /**
     * Get rendered total profit for given subscription profile.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @param bool $addContainer
     *
     * @return string
     * @throws LocalizedException
     */
    public function getRenderedTotalProfit(SubscriptionProfile $subscriptionProfile, $addContainer = true)
    {
        if ($subscriptionProfile->getStatus() === ProfileStatus::STATUS_CANCELED) {
            $profit = $this->getTotalProfit($subscriptionProfile);
        } else {
            $profit = $this->getAsOfTodayProfit($subscriptionProfile);
        }
        return $this->renderPrice($profit, $subscriptionProfile, $addContainer);
    }

    /**
     * Get actual profit for today for given subscription profile.
     * As of today profit equals (product price - product cost) * product amount from all paid quotes(has order).
     *
     * @param SubscriptionProfile $subscriptionProfile
     *
     * @return float|int
     * @throws LocalizedException
     */
    public function getAsOfTodayProfit(SubscriptionProfile $subscriptionProfile)
    {
        if (empty($this->asOfTodayProfit[$subscriptionProfile->getId()])) {
            $this->asOfTodayProfit[$subscriptionProfile->getId()]
                = $this->getProfit($subscriptionProfile, self::AS_OF_TODAY);
        }

        return $this->asOfTodayProfit[$subscriptionProfile->getId()];
    }

    /**
     * Get rendered actual profit for today for given subscription profile.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @param bool $includeContainer
     *
     * @return string
     * @throws LocalizedException
     */
    public function getRenderedAsOfTodayProfit(SubscriptionProfile $subscriptionProfile, $includeContainer = true)
    {
        $profit = $this->getAsOfTodayProfit($subscriptionProfile);
        return $this->renderPrice($profit, $subscriptionProfile, $includeContainer);
    }

    /**
     * Get potential profit for given subscription profile.
     * Remaining profit equals (product price - product cost) * product amount from all non paid quotes(has no order).
     *
     * @param SubscriptionProfile $subscriptionProfile
     *
     * @return float|int
     * @throws LocalizedException
     */
    public function getRemainingProfit(SubscriptionProfile $subscriptionProfile)
    {
        if (empty($this->remainingProfit[$subscriptionProfile->getId()])) {
            $this->remainingProfit[$subscriptionProfile->getId()]
                =  $this->getProfit($subscriptionProfile, self::REMAINING);
        }

        return $this->remainingProfit[$subscriptionProfile->getId()];
    }

    /**
     * Get rendered potential profit for given subscription profile.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @param bool $includeContainer
     *
     * @return float|int
     * @throws LocalizedException
     */
    public function getRenderedRemainingProfit(SubscriptionProfile $subscriptionProfile, $includeContainer = true)
    {
        $profit = $this->getRemainingProfit($subscriptionProfile);
        return $this->renderPrice($profit, $subscriptionProfile, $includeContainer);
    }

    /**
     * Calculate profit for subscription profile depends on requested profit type.
     *
     * @param SubscriptionProfile $profile
     * @param string $profitType
     * @return float|int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProfit(SubscriptionProfile $profile, $profitType)
    {
        return $this->subscriptionProfile->getTotalProfit($profile->getEntityId(), $profitType);
    }

    /**
     * Render price.
     *
     * @param string $value
     * @param SubscriptionProfile $subscriptionProfile
     * @param bool $addContainer
     * @return string
     */
    private function renderPrice($value, SubscriptionProfile $subscriptionProfile, $addContainer = true)
    {
        $profileCurrencyCode = $subscriptionProfile->getProfileCurrencyCode();
        $this->currency->setCurrencyCode($profileCurrencyCode);
        return $this->currency->format($value, [], $addContainer);
    }
}
