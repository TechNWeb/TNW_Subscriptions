<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\UrlInterface;
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
     * Url Builder
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param QuoteSessionInterface $session
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        QuoteSessionInterface $session,
        UrlInterface $urlBuilder
    ) {
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        $itemsQty = 0;
        $href = 'javascript:';
        $subQuotes = $this->session->getSubQuotes();

        foreach ($subQuotes as $quote) {
            $itemsQty += (int)$quote->getItemsQty();
        }

        if ($itemsQty > 0) {
            $href = $this->urlBuilder->getUrl('tnw_subscriptions/cart/index');
        }

        return [
            'itemsCount' => $itemsQty,
            'href' => $href,
        ];
    }
}
