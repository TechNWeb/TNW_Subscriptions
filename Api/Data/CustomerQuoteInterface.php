<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

/**
 * Interface CustomerQuoteInterface
 */
interface CustomerQuoteInterface
{
    /**#@+
     * Constants for field names
     */
    const ID = 'id';
    const QUOTE_ID = 'quote_id';
    const CUSTOMER_ID = 'customer_id';
    /**#@-*/

    /**
     * Gets id.
     *
     * @return string|null
     */
    public function getId();

    /**
     * Sets id.
     *
     * @param string $id
     * @return $this
     */
    public function setId($id);

    /**
     * Gets quote id.
     *
     * @return string|null
     */
    public function getQuoteId();

    /**
     * Sets quote id.
     *
     * @param string $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId);

    /**
     * Gets customer id.
     *
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Sets customer id.
     *
     * @param string $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

}