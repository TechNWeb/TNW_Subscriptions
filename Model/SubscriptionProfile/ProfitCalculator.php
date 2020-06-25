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
     * @param SubscriptionProfile $profile
     * @param string $profitType
     * @return float|int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProfit(SubscriptionProfile $profile, $profitType)
    {
        $profit = 0;

        $resource = $profile->getResource();
        $connection = $resource->getConnection();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
                array_map([$this, 'profileItemProductId'], $profile->getProducts()),
                'in'
            )
            ->addFilter(ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID, $profile->getBillingFrequencyId())
            ->create();

        $recurringOptions = $this->recurringOptionRepository
            ->getList($searchCriteria)
            ->getItems();

        /** @var ProductSubscriptionProfileInterface $profileProduct */
        foreach ($profile->getVisibleProducts() as $profileProduct) {
            $children = $profileProduct->getChildren();

            if (!empty($children) &&
                $this->searchRecurringOption($recurringOptions, \reset($children)->getMagentoProductId())
            ) {
                $profileProduct = \reset($children);
            }

            $product = $profileProduct->getMagentoProduct();
            $recurringOption = $this->searchRecurringOption($recurringOptions, $product->getId());

            if (empty($recurringOption)) {
                continue;
            }
            $select = $connection->select()
                ->from(
                    ['invoiceItem' => $resource->getTable('sales_invoice_item')]
                )
                ->joinInner(
                    ['salesRelative' => $resource->getTable('tnw_subscriptions_profile_item_sales_item')],
                    'invoiceItem.order_item_id = salesRelative.order_item_id',
                    []
                )
                ->joinInner(
                    ['profileItem' => $resource->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                    'salesRelative.profile_item_id = profileItem.entity_id',
                    []
                )
                ->where('profileItem.subscription_profile_id = ?', $profile->getId());

            $invoiceItems = $connection->fetchAll($select);
            foreach ($invoiceItems as $item) {
                $profit += ($item['base_price'] - $item['base_cost']) * $item['qty'];
            }

            switch ($profitType) {
                case self::AS_OF_TODAY:
                    break;
                case self::REMAINING:
                    $lastInvoiceItem = array_pop($invoiceItems);
                    $profitOfLastItem = ($lastInvoiceItem['base_price'] - $lastInvoiceItem['base_cost'])
                        * $lastInvoiceItem['qty'];

                    if ($profile->getTerm() == 1) {
                        if ($profile->getUnit() == 3) {
                            $profit = $profitOfLastItem * 365 / $profile->getFrequency();
                            break;
                        } else {
                            $profit = $profitOfLastItem * 12 / $profile->getFrequency();
                            break;
                        }
                    } else {
                        $profit += $profitOfLastItem * $profile->getTotalBillingCycles();
                        return $profit;
                        break;
                    }
                default:
                    return null;
                    break;
            }
        }
        return $profit;
    }

    /**
     * @param $recurringOptions
     * @param $productId
     *
     * @return ProductBillingFrequencyInterface|false
     */
    private function searchRecurringOption($recurringOptions, $productId)
    {
        $filteredRecurringOptions = array_filter(
            $recurringOptions,
            function (ProductBillingFrequencyInterface $frequency) use ($productId) {
                return (int)$frequency->getMagentoProductId() === (int)$productId;
            }
        );

        return \reset($filteredRecurringOptions);
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     *
     * @return null|string
     */
    private function profileItemProductId(ProductSubscriptionProfileInterface $item)
    {
        return $item->getMagentoProductId();
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
