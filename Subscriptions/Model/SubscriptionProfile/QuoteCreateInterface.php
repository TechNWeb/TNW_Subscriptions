<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Interface for creating subscription quote.
 */
interface QuoteCreateInterface
{
    /**
     * Creates empty quote and assigns customer if there is a customer id in session.
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function createSubCart();

    /**
     * Returns repository for retrieving quotes.
     *
     * @return CartRepositoryInterface
     */
    public function getCartRepository();
}
