<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Directory\Model\Currency;
use Magento\Framework\Api\SearchCriteriaBuilder;
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
     * @var float|int
     */
    private $remainingProfit;

    /**
     * @var float|int
     */
    private $asOfTodayProfit;

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
     * ProfitCalculator constructor.
     *
     * @param ProductBillingFrequencyRepositoryInterface $recurringOptionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Currency $currency
     */
    public function __construct(
        ProductBillingFrequencyRepositoryInterface $recurringOptionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Currency $currency
    ) {
        $this->recurringOptionRepository = $recurringOptionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->currency = $currency;
    }

    /**
     * Get total profit for given subscription profile.
     * Total profit equals "as of today" profit + "remaining" profit.
     *
     * @param SubscriptionProfile $subscriptionProfile
     *
     * @return float|int
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAsOfTodayProfit(SubscriptionProfile $subscriptionProfile)
    {
        if ($this->asOfTodayProfit === null) {
            $this->asOfTodayProfit = $this->getProfit($subscriptionProfile, self::AS_OF_TODAY);
        }

        return $this->asOfTodayProfit;
    }

    /**
     * Get rendered actual profit for today for given subscription profile.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @param bool $includeContainer
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRemainingProfit(SubscriptionProfile $subscriptionProfile)
    {
        if ($this->remainingProfit === null) {
            $this->remainingProfit =  $this->getProfit($subscriptionProfile, self::REMAINING);
        }

        return $this->remainingProfit;
    }

    /**
     * Get rendered potential profit for given subscription profile.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @param bool $includeContainer
     *
     * @return float|int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRenderedRemainingProfit(SubscriptionProfile $subscriptionProfile, $includeContainer = true)
    {
        $profit = $this->getRemainingProfit($subscriptionProfile);
        return $this->renderPrice($profit, $subscriptionProfile, $includeContainer);
    }

    /**
     * Calculate profit for subscription profile depends on requested profit type.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @param string $profitType
     * @return float|int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProfit(SubscriptionProfile $subscriptionProfile, $profitType)
    {
        $profit = 0;

        $resource = $subscriptionProfile->getResource();
        $connection = $resource->getConnection();

        /** @var ProductSubscriptionProfileInterface $profileProduct */
        foreach ($subscriptionProfile->getVisibleProducts() as $profileProduct) {
            $product = $profileProduct->getMagentoProduct();
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(
                    ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
                    $product->getId()
                )
                ->addFilter(
                    ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID,
                    $subscriptionProfile->getBillingFrequencyId()
                )
                ->create();

            $recurringOptions = $this->recurringOptionRepository->getList($searchCriteria)->getItems();

            if (empty($recurringOptions)) {
                continue;
            }

            $select = $connection->select()
                ->from($resource->getTable('tnw_subscriptions_subscription_profile_order'), ['COUNT(*)'])
                ->where('subscription_profile_id = ?', $subscriptionProfile->getId());

            switch ($profitType) {
                case self::AS_OF_TODAY:
                    $select->where('magento_order_id IS NOT NULL');
                    break;

                case self::REMAINING:
                default:
                    $select->where('magento_order_id IS NULL');
                    break;
            }

            $qty = $profileProduct->getQty() * $connection->fetchOne($select);

            $cost = $product->getCost();
            $children = $profileProduct->getChildren();
            if (empty($cost) && !empty($children)) {
                $cost = \reset($children)->getMagentoProduct()->getCost();
            }

            $profit += (\reset($recurringOptions)->getPrice() - $cost) * $qty;
        }

        return $profit;
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
