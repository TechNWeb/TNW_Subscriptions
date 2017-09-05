<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Create;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Address;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\QuoteCreateInterface;

/**
 * Create Quote for subscription profile on storefront.
 */
class Quote extends Create implements QuoteCreateInterface
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var GroupManagementInterface
     */
    private $groupManagement;

    /**
     * @var Address
     */
    private $addressCreator;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Quote constructor.
     *
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param QuoteFactory $quoteFactory
     * @param GroupManagementInterface $groupManagement
     * @param Address $addressCreator
     * @param CartRepositoryInterface $cartRepository
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        QuoteFactory $quoteFactory,
        GroupManagementInterface $groupManagement,
        Address $addressCreator,
        CartRepositoryInterface $cartRepository,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context, $session);
        $this->quoteFactory = $quoteFactory;
        $this->groupManagement = $groupManagement;
        $this->addressCreator = $addressCreator;
        $this->cartRepository = $cartRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritdoc
     */
    public function createSubCart()
    {
        /** @var \TNW\Subscriptions\Model\Session\Quote $session */
        $session = $this->getSession();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();
        $customerGroupId = $session->getCustomerGroupId() ?: $this->groupManagement->getDefaultGroup()->getId();
        $quote->setCustomerGroupId($customerGroupId);
        $quote->setIsActive(false);
        $quote->setStoreId($session->getStoreId());
        if (!$session->getCustomerId()) {
            $quote->setBillingAddress($this->addressCreator->getEmptyAddress());
        } else {
            $quote->setCustomerAddressData($session->getCustomer()->getAddresses());
        }
        $subQuotes = $session->getSubQuotes();
        $donorQuote = null;
        if (count($subQuotes)) {
            /** @var \Magento\Quote\Model\Quote $donorQuote */
            $donorQuote = reset($subQuotes);
            /** @var AddressInterface $shippingAddressData */
            $shippingAddressData = $donorQuote->getShippingAddress()->exportCustomerAddress();
            $quote->getShippingAddress()->importCustomerAddressData($shippingAddressData);
            $quote->getShippingAddress()->setCollectShippingRates(true);
        } else {
            if (!$session->getCustomerId()) {
                $quote->setShippingAddress($this->addressCreator->getEmptyAddress());
            }
        }
        $this->cartRepository->save($quote);
        $quote = $this->cartRepository->get($quote->getId(), [$session->getStoreId()]);

        if ($session->getCustomerId() &&
            $session->getCustomerId() !== $quote->getCustomerId()
        ) {
            $customer = $this->customerRepository->getById($session->getCustomerId());
            $quote->assignCustomer($customer);
            $quote->setTotalsCollectedFlag(true);
            $this->cartRepository->save($quote);
        }

        return $quote;
    }
}
