<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
     * @param Context $context
     * @param QuoteCollection $quoteCollection
     * @param PriceHelper $priceHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        QuoteCollection $quoteCollection,
        PriceHelper $priceHelper,
        array $data = []
    ) {
        $this->quoteCollection = $quoteCollection;
        $this->priceHelper = $priceHelper;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve next payment date
     *
     * @return \DateTime|false
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
     * @return array
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
        if ($this->grandTotal === null) {
            $quote = $this->getNextQuote();
            if (!$quote || !$quote->getId()) {
                $this->grandTotal = false;
            } else {
                $this->grandTotal = (float)$quote->getGrandTotal();
            }
        }
        return $this->grandTotal;
    }

    /**
     * Retrieve Cost with price formatting
     * 
     * @return string
     */
    public function getCostFormatting()
    {
        $grandTotal = $this->getCost();
        if ($grandTotal === false) {
            return '--';
        } else {
            return $this->priceHelper->currency($grandTotal, true, false);
        }
    }
}
