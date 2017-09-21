<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use TNW\Subscriptions\Model\QuoteSession;

/**
 * Section to reload cart items qty.
 */
class SubscriptionCart implements SectionSourceInterface
{
    /**
     * @var QuoteSession
     */
    private $session;

    /**
     * @param QuoteSession $session
     */
    public function __construct(QuoteSession $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        $itemsQty = 0;
        $subQuotes = $this->session->getSubQuotes();

        foreach ($subQuotes as $quote) {
            $itemsQty += (int)$quote->getItemsQty();
        }

        return ['itemsCount' => $itemsQty];
    }
}
