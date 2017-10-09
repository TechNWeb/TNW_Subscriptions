<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Directory\Model\Currency;
use Magento\Reports\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Calculate "total" profit, "remaining" profit and "as of today" profit for given subscription profile.
 */
class ProfitCalculator
{
    /** Profit types. */
    const AS_OF_TODAY = 'as_of_today';
    const REMAINING = 'remaining';

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var QuoteItemCollectionFactory
     */
    private $quoteItemCollectionFactory;

    /**
     * Product list cache.
     *
     * @var ProductInterface[]
     */
    private $products;

    /**
     * @var float|int
     */
    private $remainingProfit;

    /**
     * @var float|int
     */
    private $asOfTodayProfit;
    /**
     * @var Currency
     */
    private $currency;

    /**
     * ProfitCalculator constructor.
     *
     * @param ProductCollectionFactory $productCollectionFactory
     * @param QuoteItemCollectionFactory $quoteItemCollectionFactory
     * @param Currency $currency
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        QuoteItemCollectionFactory $quoteItemCollectionFactory,
        Currency $currency
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        $this->currency = $currency;
    }

    /**
     * Get total profit for given subscription profile.
     * Total profit equals "as of today" profit + "remaining" profit.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @return float|int
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
     * @return string
     */
    public function getRenderedTotalProfit(SubscriptionProfile $subscriptionProfile, $addContainer = true)
    {
        $profit = $this->getTotalProfit($subscriptionProfile);
        return $this->renderPrice($profit, $subscriptionProfile, $addContainer);
    }

    /**
     * Get actual profit for today for given subscription profile.
     * As of today profit equals (product price - product cost) * product amount from all paid quotes(has order).
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @return float|int
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
     * @return string
     */
    public function getRenderedAsOfTodayProfit(SubscriptionProfile $subscriptionProfile)
    {
        $profit = $this->getAsOfTodayProfit($subscriptionProfile);
        return $this->renderPrice($profit, $subscriptionProfile);
    }

    /**
     * Get potential profit for given subscription profile.
     * Remaining profit equals (product price - product cost) * product amount from all non paid quotes(has no order).
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @return float|int
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
     * @return float|int
     */
    public function getRenderedRemainingProfit(SubscriptionProfile $subscriptionProfile)
    {
        $profit = $this->getRemainingProfit($subscriptionProfile);
        return $this->renderPrice($profit, $subscriptionProfile);
    }

    /**
     * Calculate profit for subscription profile depends on requested profit type.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @param string $profitType
     * @return float|int
     */
    private function getProfit(SubscriptionProfile $subscriptionProfile, $profitType)
    {
        $profit = 0;
        $products = $this->getProducts($subscriptionProfile);
        foreach ($products as $product) {
            switch ($profitType) {
                case self::AS_OF_TODAY:
                    $quoteIds = $this->getQuoteIds($subscriptionProfile, self::AS_OF_TODAY);
                    break;
                case self::REMAINING:
                default:
                    $quoteIds = $this->getQuoteIds($subscriptionProfile, self::REMAINING);
                    break;
            }
            $amount = $this->getRequestedProductAmount($quoteIds, $product->getId());
            $profit += ($product->getPrice() - $product->getCost()) * $amount;
        }

        return $profit;
    }

    /**
     * Get products used in subscription profile.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @return ProductInterface[]
     */
    private function getProducts(SubscriptionProfile $subscriptionProfile)
    {
        if ($this->products === null) {
            $productIds = $this->getProductIds($subscriptionProfile);
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect('price');
            $productCollection->addAttributeToSelect('cost');
            $productCollection->addIdFilter($productIds);
            $this->products = $productCollection->getItems();
        }

        return $this->products;
    }

    /**
     * Get requested product qty for given quotes.
     *
     * @param array $quoteIds
     * @param string $productId
     * @return int
     */
    private function getRequestedProductAmount($quoteIds, $productId)
    {
        $amount = 0;
        $quoteItemCollection = $this->quoteItemCollectionFactory->create();
        $quoteItemCollection->addFieldToFilter('quote_id', ['in' => $quoteIds]);
        $quoteItemCollection->addFieldToFilter('product_id', ['eq' => $productId]);
        foreach ($quoteItemCollection->getItems() as $item) {
            $amount += $item->getQty();
        }

        return $amount;
    }

    /**
     * Get magento product ids used in given subscription profile.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @return array
     */
    private function getProductIds(SubscriptionProfile $subscriptionProfile)
    {
        $productIds = [];
        foreach ($subscriptionProfile->getProducts() as $profileProduct) {
            $productIds = $profileProduct->getMagentoProductId();
        }

        return $productIds;
    }

    /**
     * Get quote ids for given subscription profile depends on profit type.
     *
     * @param SubscriptionProfile $subscriptionProfile
     * @param string $type
     * @return array
     */
    private function getQuoteIds(SubscriptionProfile $subscriptionProfile, $type)
    {
        $resource = $subscriptionProfile->getResource();
        $sql = $resource->getConnection()
            ->select()->from(
                $resource->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE),
                [SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID]
            )->where(SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID . '= ?', $subscriptionProfile->getId());
        if ($type === self::AS_OF_TODAY) {
            $sql->join(
                'sales_order', 'sales_order.entity_id = '
                . $resource->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)
                . '.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID,
                []
            )->where(
                new \Zend_Db_Expr(SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID . ' is not null')
            )->where('sales_order.' . OrderInterface::STATUS . ' != ?', Order::STATE_CANCELED);
        } elseif ($type === self::REMAINING) {
            $sql->where(new \Zend_Db_Expr(SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID . ' is null'));
        }

        return $resource->getConnection()->fetchCol($sql);
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
