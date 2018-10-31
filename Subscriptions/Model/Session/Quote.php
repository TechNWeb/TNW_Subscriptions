<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Session;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use TNW\Subscriptions\Model\QuoteSession;

/**
 * Storefront session for subscription quote.
 */
class Quote extends QuoteSession
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return (int)$this->getCustomerSession()->getCustomerId();
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return (int)$this->getCurrentStore()->getId();
    }

    /**
     * @return string
     */
    public function getCurrencyId()
    {
        return (string)$this->getCurrentStore()->getCurrentCurrencyCode();
    }

    /**
     * @return int
     */
    public function getCustomerGroupId()
    {
        return (int)$this->getCustomerSession()->getCustomerGroupId();
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        return $this->getCustomerSession()->getCustomer();
    }

    /**
     * @return int|float
     */
    public function getSubQuoteItemsCount()
    {
        $qty = 0;
        foreach ($this->getSubQuotes() as $quote) {
            $qty += $quote->getItemsQty();
        }

        return $qty;
    }

    /**
     * @inheritdoc
     */
    public function getSubQuotes()
    {
        $quote = $this->getCheckoutSession()->getQuote();
        if (empty($quote->getAllVisibleItems())) {
            return parent::getSubQuotes();
        }

        return array_merge([$quote->getId() => $quote], parent::getSubQuotes());
    }

    /**
     * @return CustomerSession
     */
    private function getCustomerSession()
    {
        if ($this->customerSession === null) {
            $this->customerSession = ObjectManager::getInstance()->get(CustomerSession::class);
        }

        return $this->customerSession;
    }

    /**
     * @return CheckoutSession
     */
    private function getCheckoutSession()
    {
        if ($this->checkoutSession === null) {
            $this->checkoutSession = ObjectManager::getInstance()->get(CheckoutSession::class);
        }

        return $this->checkoutSession;
    }

    /**
     * @return StoreInterface
     */
    private function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Add customer data for loaded quotes, if one has been created during guest session.
     *
     * @return void
     */
    protected function processQuote()
    {
        foreach ($this->quotes as $quote) {
            if (!$quote->getCustomerId() && $this->getCustomerId()) {
                $customer = ObjectManager::getInstance()->get(CustomerRepositoryInterface::class)
                    ->getById($this->getCustomerId());
                $quote->assignCustomer($customer);
                $quote->setCustomerAddressData($this->getCustomer()->getAddresses());
            }
        }
    }

    /**
     * Get profile ids.
     *
     * @return array
     */
    public function getProfileIds()
    {
        return ($this->storage->getProfileIds()) ? $this->storage->getProfileIds() : [];
    }

    /**
     * Set profile ids.
     *
     * @param array $profileIds
     * @return $this
     */
    public function setProfileIds($profileIds)
    {
        $this->storage->setProfileIds($profileIds);
        return $this;
    }
}
