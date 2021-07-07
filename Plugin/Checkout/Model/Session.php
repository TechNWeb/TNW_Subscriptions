<?php
/**
 *  Copyright © 2021 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\Model;

use ArrayObject;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Api\CustomerProductHistoryManagementInterface;

/**
 * Class Session - Trial availability checker on load customer quote action
 */
class Session
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CustomerProductHistoryManagementInterface
     */
    private $customerProductHistoryManagement;

    /**
     * Session constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param CustomerProductHistoryManagementInterface $customerProductHistoryManagement
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CustomerProductHistoryManagementInterface $customerProductHistoryManagement
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->customerProductHistoryManagement = $customerProductHistoryManagement;
    }

    /**
     * @param CheckoutSession $checkoutSession
     * @param CheckoutSession $result
     * @return CheckoutSession
     */
    public function afterLoadCustomerQuote(
        CheckoutSession $checkoutSession,
        CheckoutSession $result
    ) {
        if (!$result->getQuoteId()) {
            return $result;
        }

        try {
            $addProductList = new ArrayObject();
            $quote = $result->getQuote();
            foreach ($quote->getAllItems() as $item) {
                if ($item->getChildren()) {
                    continue;
                }
                $buyRequest = $item->getBuyRequest();
                if (($subscriptionData = $buyRequest->getData('subscription_data'))
                    && isset($subscriptionData['unique']['is_trial'])
                    && $subscriptionData['unique']['is_trial'] === true
                    && $quote->getData('customer_id')
                ) {
                    if (!$this->customerProductHistoryManagement->isProductTrialAvailableForCustomer(
                        $quote->getData('customer_id'),
                        $item->getProduct()->getId()
                    )) {
                        $itemToDelete = $item;
                        if ($item->getParentItemId()) {
                            $itemToDelete = $item->getParentItem();
                            $buyRequest = $itemToDelete->getBuyRequest();
                        }
                        $buyRequest->setData('modify_profile', true);
                        foreach (['custom_price', 'subscription_data', 'use_preset_qty', 'hide_qty'] as $key) {
                            $buyRequest->unsetData($key);
                        }

                        $quote->deleteItem($itemToDelete);
                        $addProductList->append([
                            'item' => clone $itemToDelete,
                            'itemProduct' => clone $itemToDelete->getProduct(),
                            'buyRequest' => clone $buyRequest,
                        ]);
                    }
                }
            }
            if ($addProductList->count()) {
                foreach ($addProductList->getIterator() as $itemToAdd) {
                    $quote->addProduct($itemToAdd['itemProduct'], $itemToAdd['buyRequest']);
                }
                $this->quoteRepository->save($quote);

                /** @var Quote $quote */
                $quote = $this->quoteRepository->get($quote->getId());
                $quote->setTotalsCollectedFlag(false)->collectTotals();
                $this->quoteRepository->save($quote);
                $result->replaceQuote($quote);
            }
        } catch (NoSuchEntityException | LocalizedException $e) {
            return $result;
        }
        return $result;
    }
}
