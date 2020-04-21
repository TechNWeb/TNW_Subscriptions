<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Create;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use TNW\Subscriptions\Api\CustomerQuoteRepositoryInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Address;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Quote as AdminQuote;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

/**
 * Create Quote for subscription profile on storefront.
 */
class Quote extends AdminQuote
{
    /**
     * Customer Quote Repository
     *
     * @var CustomerQuoteRepositoryInterface
     */
    private $customerQuoteRepository;

    /**
     * Quote constructor.
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param QuoteFactory $quoteFactory
     * @param GroupManagementInterface $groupManagement
     * @param Address $addressCreator
     * @param CartRepositoryInterface $cartRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param FormFactory $customerFormFactory
     * @param Mapper $customerMapper
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param CustomerQuoteRepositoryInterface $customerQuoteRepository
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        QuoteFactory $quoteFactory,
        GroupManagementInterface $groupManagement,
        Address $addressCreator,
        CartRepositoryInterface $cartRepository,
        CustomerRepositoryInterface $customerRepository,
        FormFactory $customerFormFactory,
        Mapper $customerMapper,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        CustomerQuoteRepositoryInterface $customerQuoteRepository
    ) {
        $this->customerQuoteRepository = $customerQuoteRepository;
        parent::__construct(
            $context,
            $session,
            $quoteFactory,
            $groupManagement,
            $addressCreator,
            $cartRepository,
            $customerRepository,
            $customerFormFactory,
            $paymentTokenManagement,
            $paymentTokenRepository,
            $customerMapper
        );
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
        if ($session->getCustomerId()) {
            $this->customerQuoteRepository->saveCustomerQuote($session->getCustomerId(), $quote->getId());
        }

        return $quote;
    }
}
