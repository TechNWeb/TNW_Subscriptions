<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\Metadata\Form;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory as ModelQuoteFactory;
use TNW\Subscriptions\Model\Backend\Session\Quote as Session;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;

/**
 * Class Quote
 */
class Quote extends Create
{
    /**
     * Factory for creating quotes.
     *
     * @var ModelQuoteFactory
     */
    private $quoteFactory;

    /**
     * Customer group manager.
     *
     * @var GroupManagementInterface
     */
    private $groupManagement;

    /**
     * Quote addresses creator.
     *
     * @var Address
     */
    private $addressCreator;

    /**
     * Repository for retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * Repository for retrieving customers.
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Factory for creating customer metadata form.
     *
     * @var FormFactory
     */
    private $customerFormFactory;

    /**
     * Customer mapper.
     *
     * @var Mapper
     */
    private $customerMapper;

    /**
     * Quote constructor.
     * @param Context $context
     * @param SessionManagerInterface $session
     * @param ModelQuoteFactory $quoteFactory
     * @param GroupManagementInterface $groupManagement
     * @param Address $addressCreator
     * @param CartRepositoryInterface $cartRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param FormFactory $customerFormFactory
     * @param Mapper $customerMapper
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $session,
        ModelQuoteFactory $quoteFactory,
        GroupManagementInterface $groupManagement,
        Address $addressCreator,
        CartRepositoryInterface $cartRepository,
        CustomerRepositoryInterface $customerRepository,
        FormFactory $customerFormFactory,
        Mapper $customerMapper
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->groupManagement = $groupManagement;
        $this->addressCreator = $addressCreator;
        $this->cartRepository = $cartRepository;
        $this->customerRepository = $customerRepository;
        $this->customerFormFactory = $customerFormFactory;
        $this->customerMapper = $customerMapper;
        parent::__construct($context, $session);
    }

    /**
     * Returns repository for retrieving quotes.
     *
     * @return CartRepositoryInterface
     */
    public function getCartRepository()
    {
        return $this->cartRepository;
    }

    /**
     * Creates empty quote and assigns customer if there is a customer id in session.
     *
     * @return int|string
     */
    public function createSubCart()
    {
        /** @var ModelQuote $quote */
        $quote = $this->quoteFactory->create();
        /** @var Session $session */
        $session = $this->getSession();

        if ($session->getStoreId()) {
            $quote->setCustomerGroupId($this->groupManagement->getDefaultGroup()->getId());
            $quote->setIsActive(false);
            $quote->setStoreId($session->getStoreId());

            if (!$session->getCustomerId()) {
                $quote->setBillingAddress($this->addressCreator->getEmptyAddress());
            }

            $this->setShippingAddress($quote);

            $this->cartRepository->save($quote);
            $quote = $this->cartRepository->get($quote->getId(), [$this->getSession()->getStoreId()]);

            if ($session->getCustomerId() &&
                $session->getCustomerId() !== $quote->getCustomerId()
            ) {
                $customer = $this->customerRepository->getById($session->getCustomerId());
                $quote->assignCustomer($customer);
                $quote->setTotalsCollectedFlag(true);
                $this->cartRepository->save($quote);
            }
        }

        $quote->setData('ignore_old_qty', true);
        $quote->setData('is_super_mode', true);

        return $quote;
    }

    /**
     * Set Shipping address for quote
     *
     * Set Shipping address from old quote if we already have quote.
     * Otherwise create empty address except case when we have customer.
     *
     * @param ModelQuote $quote
     * @return $this
     */
    private function setShippingAddress(ModelQuote $quote)
    {
        $subQuotes = $this->getSession()->getSubQuotes();
        $donorQuote = null;
        if (count($subQuotes)) {
            /** @var ModelQuote $donorQuote */
            $donorQuote = reset($subQuotes);
            /** @var AddressInterface $shippingAddressData */
            $shippingAddressData = $donorQuote->getShippingAddress()->exportCustomerAddress();
            $quote->getShippingAddress()->importCustomerAddressData($shippingAddressData);
            $quote->getShippingAddress()->setCollectShippingRates(true);
        } else {
            if (!$this->getSession()->getCustomerId()) {
                $quote->setShippingAddress($this->addressCreator->getEmptyAddress());
            }
        }

        return $this;
    }

    /**
     * Sets into quote customer data.
     *
     * @param CustomerInterface $customer
     */
    public function fillCustomerData(CustomerInterface $customer)
    {
        $quoteData = [];
        $origAddresses = $customer->getAddresses(); // save original addresses
        $customer->setAddresses([]);
        $data = $this->customerMapper->toFlatArray($customer);
        $customer->setAddresses($origAddresses); // restore original addresses

        foreach ($this->getCustomerForm($customer)->getUserAttributes() as $attribute) {
            if (isset($data[$attribute->getAttributeCode()])) {
                $quoteCode = sprintf('customer_%s', $attribute->getAttributeCode());
                $quoteData[$quoteCode] = $data[$attribute->getAttributeCode()];
            }
        }
        /** @var Session $session */
        $session = $this->getSession();

        foreach ($session->getSubQuotes() as $subQuote) {
            foreach ($quoteData as $code => $value) {
                $subQuote->setData($code, $value);
            }
        }
    }

    /**
     * Returns customer form instance.
     *
     * @param CustomerInterface $customer
     * @return Form
     */
    private function getCustomerForm(CustomerInterface $customer)
    {
        $customerForm = $this->customerFormFactory->create(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            'adminhtml_checkout',
            $this->customerMapper->toFlatArray($customer),
            false,
            Form::IGNORE_INVISIBLE
        );

        return $customerForm;
    }


    /**
     * @param ModelQuote $quote
     * @return array
     * @throws \Exception
     */
    public function validate(ModelQuote $quote)
    {
        $errors = [];
        /** @var Session $session */
        $session = $this->getSession();

        if (!$session->getStore()->getId()) {
            throw new \Exception(__('Please select a store'));
        }
        $items = $quote->getAllItems();

        if (count($items) == 0) {
            $errors[] = __('Please specify order items.');
        }

        /** @var Item $item */
        foreach ($items as $item) {
            $messages = $item->getMessage(false);
            if ($item->getHasError() && is_array($messages) && !empty($messages)) {
                $errors = array_merge($errors, $messages);
            }
        }

        if (!$quote->isVirtual()) {
            if (!$quote->getShippingAddress()->getShippingMethod()) {
                $errors[] = __('Please specify a shipping method.');
            }
        }

        if (!$quote->getPayment()->getMethod()) {
            $errors[] = __('Please specify a payment method.');
        } else {
            $method = $quote->getPayment()->getMethodInstance();
            if (!$method->isAvailable($quote)) {
                $errors[] = __('This payment method is not available.');
            } else {
                try {
                    $method->validate();
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        return $errors;
    }
}