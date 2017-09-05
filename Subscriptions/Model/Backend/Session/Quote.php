<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Backend\Session;

use TNW\Subscriptions\Model\QuoteSession;

/**
 * Admin session for subscription quote.
 */
class Quote extends QuoteSession
{
    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    /**
     * @return string
     */
    public function getCurrencyId()
    {
        return $this->getData('currency_id');
    }
}
