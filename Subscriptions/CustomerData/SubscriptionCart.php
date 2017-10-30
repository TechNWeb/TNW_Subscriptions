<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Section to reload cart items qty.
 */
class SubscriptionCart implements SectionSourceInterface
{
    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @param QuoteSessionInterface $session
     */
    public function __construct(QuoteSessionInterface $session)
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
