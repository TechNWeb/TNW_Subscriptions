<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Cart;

use Magento\Framework\App\Action;

class UpdatePost extends Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \TNW\Subscriptions\Model\QuoteSessionInterface
     */
    private $quoteSession;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $localeResolver;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    private $stockState;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->quoteSession = $quoteSession;
        $this->localeResolver = $localeResolver;
        $this->stockState = $stockState;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * Update shopping cart data action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (\is_array($cartData)) {
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = \Zend_Locale_Format::getNumber(
                            trim($data['qty']),
                            ['locale' => $this->localeResolver->getLocale()]
                        );
                    }
                }

                $quotes = \array_merge(
                    $this->quoteSession->getSubQuotes(),
                    [$this->checkoutSession->getQuote()]
                );

                /** @var \Magento\Quote\Model\Quote $quote */
                foreach ($quotes as $quote) {
                    $cartData = $this->suggestItemsQty($quote, $cartData);
                    $this->updateItems($quote, $cartData);

                    $quote->getBillingAddress();
                    $quote->getShippingAddress()->setCollectShippingRates(true);
                    $quote->collectTotals();
                    $this->quoteRepository->save($quote);
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the shopping cart.'));
            $this->logger->critical($e);
        }

        return $this->resultRedirectFactory->create()
            ->setRefererUrl();
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $data
     *
     * @return mixed
     */
    private function suggestItemsQty($quote, $data)
    {
        foreach ($data as $itemId => $itemInfo) {
            if (!isset($itemInfo['qty'])) {
                continue;
            }

            $qty = (float)$itemInfo['qty'];
            if ($qty <= 0) {
                continue;
            }

            $quoteItem = $quote->getItemById($itemId);
            if (!$quoteItem) {
                continue;
            }

            $product = $quoteItem->getProduct();
            if (!$product) {
                continue;
            }

            $data[$itemId]['before_suggest_qty'] = $qty;
            $data[$itemId]['qty'] = $this->stockState->suggestQty(
                $product->getId(),
                $qty,
                $product->getStore()->getWebsiteId()
            );
        }

        return $data;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $data
     *
     * @return \Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateItems($quote, $data)
    {
        $qtyRecalculatedFlag = false;
        foreach ($data as $itemId => $itemInfo) {
            $item = $quote->getItemById($itemId);
            if (!$item) {
                continue;
            }

            if (!empty($itemInfo['remove']) || (isset($itemInfo['qty']) && $itemInfo['qty'] == '0')) {
                $quote->removeItem($itemId);
                continue;
            }

            if (\in_array($quote->getId(), $this->quoteSession->getSubQuoteIds())
                && $item->getProduct()->getData('tnw_subscr_unlock_preset_qty')
            ) {
                continue;
            }

            $qty = isset($itemInfo['qty']) ? (double)$itemInfo['qty'] : false;
            if ($qty > 0) {
                $item->setQty($qty);

                if ($item->getHasError()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($item->getMessage()));
                }

                if (isset($itemInfo['before_suggest_qty']) && $itemInfo['before_suggest_qty'] != $qty) {
                    $qtyRecalculatedFlag = true;
                    $this->messageManager->addNoticeMessage(
                        __('Quantity was recalculated from %1 to %2', $itemInfo['before_suggest_qty'], $qty),
                        'quote_item' . $item->getId()
                    );
                }
            }
        }

        if ($qtyRecalculatedFlag) {
            $this->messageManager->addNoticeMessage(
                __('We adjusted product quantities to fit the required increments.')
            );
        }

        return $quote;
    }
}
