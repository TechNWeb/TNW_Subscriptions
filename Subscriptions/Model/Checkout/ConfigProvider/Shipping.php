<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Shipping config for Cart.
 */
class Shipping implements ConfigProviderInterface
{
    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @param QuoteSessionInterface $session
     */
    public function __construct(
        QuoteSessionInterface $session
    ) {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'isSubscriptionsVirtual' => $this->getIsVirtualFromSubscriptions(),
        ];
    }

    /**
     * Returns is virtual param from all subscriptions.
     *
     * @return bool
     */
    private function getIsVirtualFromSubscriptions()
    {
        $result = true;
        foreach ($this->session->getSubQuotes() as $quote) {
            if (!$quote->isVirtual()) {
                $result = false;
                break;
            }
        }

        return $result;
    }
}
