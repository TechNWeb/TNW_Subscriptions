<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Summary\Overview;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileOrder;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Subscription Overview Next Payment block
 *
 * @method SubscriptionProfile getSubscriptionProfile()
 * @method SubscriptionProfileOrder getNextProfileRelation()
 * @method Quote getNextQuote()
 */
class NextPayment extends Template
{
    /**
     * @inheritdoc
     */
    protected $_template = 'TNW_Subscriptions::subscription_profile/summary/overview/next-payment.phtml';

    /**
     * @var QuoteCollection
     */
    private $quoteCollection;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @var float
     */
    private $grandTotal;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceFormatter;

    /**
     * @param Context $context
     * @param QuoteCollection $quoteCollection
     * @param PriceHelper $priceHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        QuoteCollection $quoteCollection,
        PriceHelper $priceHelper,
        PriceCurrencyInterface $priceFormatter,
        array $data = []
    ) {
        $this->quoteCollection = $quoteCollection;
        $this->priceHelper = $priceHelper;
        $this->priceFormatter = $priceFormatter;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve next payment date
     *
     * @return bool|\DateTime
     * @throws \Exception
     */
    public function getNextPaymentDate()
    {
        $result = false;
        $nextPayment = $this->getNextProfileRelation();
        if ($nextPayment) {
            $result = $this->_localeDate->date(new \DateTime($nextPayment->getScheduledAt()));
        }

        return $result;
    }

    /**
     * Get next payment date as array of date parts.
     *
     * @return array|bool
     * @throws \Exception
     */
    public function getNextPaymentDateParts()
    {
        $result = false;
        $date = $this->getNextPaymentDate();
        if ($date) {
            $result = [
                'year' => $this->_localeDate->formatDateTime(
                    $date,
                    \IntlDateFormatter::SHORT,
                    \IntlDateFormatter::SHORT,
                    null,
                    null,
                    'y'
                ),
                'month' => $this->_localeDate->formatDateTime(
                    $date,
                    \IntlDateFormatter::SHORT,
                    \IntlDateFormatter::SHORT,
                    null,
                    null,
                    'MMMM'
                ),
                'day' => $this->_localeDate->formatDateTime(
                    $date,
                    \IntlDateFormatter::SHORT,
                    \IntlDateFormatter::SHORT,
                    null,
                    null,
                    'd'
                ),
            ];
        }

        return $result;
    }

    /**
     * Retrieve Cost from Quote
     *
     * @return float|false
     */
    public function getCost()
    {
        return $this->getSubscriptionProfile()->getGrandTotal();
    }

    /**
     * Retrieve Cost with price formatting
     *
     * @return string
     */
    public function getCostFormatting()
    {
        $grandTotal = $this->getSubscriptionProfile()->getGrandTotal();
        if ($grandTotal === false || $grandTotal < 0) {
            return '--';
        } else {
            $grandTotal -= $this->getShippingCost();
            return $this->priceFormatter->format(
                $grandTotal,
                false,
                null,
                null,
                $this->getSubscriptionProfile()->getProfileCurrencyCode()
            );
        }
    }

    /**
     * Retrieve Cost from Quote for shipping
     *
     * @return float|false
     */
    public function getShippingCost()
    {
        return $this->getSubscriptionProfile()->getShipping();
    }

    /**
     * Retrieve Cost for shipping price formatting
     *
     * @return string
     */
    public function getShippingCostFormatting()
    {
        $cost = '';
        if (!$this->getSubscriptionProfile()->getIsVirtual()) {
            $shippingCost = $this->getShippingCost();
            $cost = __('free');
            if ($shippingCost > 0) {
                $cost = $this->priceHelper->currency($shippingCost, true, false);
            }
            $cost = __('+ %1 (estimated shipment)', $cost);
        }

        return $cost;
    }
}
