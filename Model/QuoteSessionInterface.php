<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

/**
 * Interface for subscription quote session.
 */
interface QuoteSessionInterface extends \Magento\Framework\Session\SessionManagerInterface
{
    /**
     * @return array|null
     */
    public function getSubQuoteIds();

    /**
     * @return \Magento\Quote\Model\Quote[]
     */
    public function getSubQuotes();

    /**
     * @param $subQuoteIds
     * @return $this
     */
    public function setSubQuoteIds($subQuoteIds);

    /**
     * @return bool|\Magento\Quote\Model\Quote
     */
    public function getFirstQuote();

    /**
     * @param \Magento\Quote\Model\Quote[]|int|string $quote
     * @return $this
     */
    public function addSubQuote($quote);

    /**
     * @param \Magento\Quote\Model\Quote[]|int|string $quote
     * @return $this
     */
    public function removeSubQuote($quote);

    /**
     * @param \Magento\Quote\Model\Quote[]|int|string $quote
     *
     * @return bool
     */
    public function isSubscription($quote);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @return int|null
     */
    public function getCustomerEmail();

    /**
     * @return string
     */
    public function getCurrencyId();

    /**
     * @return int
     */
    public function getStoreId();
}
