<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\CustomerData;

use Magento\Checkout\CustomerData\ItemPoolInterface;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ItemPoolInterface
     */
    private $itemPool;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Url
     */
    private $catalogUrl;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param QuoteSessionInterface $session
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param ItemPoolInterface $itemPool
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrl
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        QuoteSessionInterface $session,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        ItemPoolInterface $itemPool,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\DataObjectFactory $dataObjectFactory
    ) {
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->itemPool = $itemPool;
        $this->catalogUrl = $catalogUrl;
        $this->dataObjectFactory = $dataObjectFactory;
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
            $itemsQty += $this->summaryCount($quote);
        }

        if ($itemsQty > 0) {
            $href = $this->urlBuilder->getUrl('tnw_subscriptions/cart');
        }

        return [
            'itemsCount' => $itemsQty,
            'subQuotes' => $this->subQuotes(),
            'href' => $href,
        ];
    }

    private function subQuotes()
    {
        $quotes = [];
        foreach ($this->session->getSubQuotes() as $subQuote) {
            $quotes[] = [
                'summaryCount' => $this->summaryCount($subQuote),
                'items' => $this->recentItems($subQuote),
                'website_id' => $subQuote->getStore()->getWebsiteId()
            ];
        }

        return $quotes;
    }

    /**
     * Get shopping cart items qty based on configuration (summary qty or items qty)
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return int|float
     */
    private function summaryCount($quote)
    {
        $useQty = $this->scopeConfig->getValue(
            'checkout/cart_link/use_qty',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $useQty ? $quote->getItemsQty() : $quote->getItemsCount();
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    private function recentItems($quote)
    {
        $items = [];
        if (!$this->summaryCount($quote)) {
            return $items;
        }

        foreach (array_reverse($quote->getAllVisibleItems()) as $item) {
            /** @var $item \Magento\Quote\Model\Quote\Item */
            if (!$item->getProduct()->isVisibleInSiteVisibility()) {
                $product =  $item->getOptionByCode('product_type') !== null
                    ? $item->getOptionByCode('product_type')->getProduct()
                    : $item->getProduct();

                $products = $this->catalogUrl->getRewriteByProductStore([$product->getId() => $item->getStoreId()]);
                if (!isset($products[$product->getId()])) {
                    continue;
                }

                $urlDataObject = $this->dataObjectFactory->create()
                    ->addData($products[$product->getId()]);

                $item->getProduct()->setUrlDataObject($urlDataObject);
            }

            $items[] = array_merge($this->itemPool->getItemData($item), [
                'subscription' => 'hello'
            ]);
        }

        return $items;
    }
}
