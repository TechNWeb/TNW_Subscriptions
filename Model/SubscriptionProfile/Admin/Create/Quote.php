<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Customer\Model\Metadata\Form;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory as ModelQuoteFactory;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\QuoteCreateInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

/**
 * Create quotes for subscription in admin area.
 */
class Quote extends Create implements QuoteCreateInterface
{
    /**
     * Factory for creating quotes.
     *
     * @var ModelQuoteFactory
     */
    protected $quoteFactory;

    /**
     * Customer group manager.
     *
     * @var GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * Quote addresses creator.
     *
     * @var Address
     */
    protected $addressCreator;

    /**
     * Repository for retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Repository for retrieving customers.
     *
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Factory for creating customer metadata form.
     *
     * @var FormFactory
     */
    protected $customerFormFactory;

    /**
     * Customer mapper.
     *
     * @var Mapper
     */
    protected $customerMapper;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     *
     *
     *
     * Quote constructor.
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param ModelQuoteFactory $quoteFactory
     * @param GroupManagementInterface $groupManagement
     * @param Address $addressCreator
     * @param CartRepositoryInterface $cartRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param FormFactory $customerFormFactory
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param Mapper $customerMapper
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        ModelQuoteFactory $quoteFactory,
        GroupManagementInterface $groupManagement,
        Address $addressCreator,
        CartRepositoryInterface $cartRepository,
        CustomerRepositoryInterface $customerRepository,
        FormFactory $customerFormFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        Mapper $customerMapper
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
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
     * @inheritdoc
     */
    public function getCartRepository()
    {
        return $this->cartRepository;
    }

    /**
     * @inheritdoc
     */
    public function createSubCart()
    {
        /** @var ModelQuote $quote */
        $quote = $this->quoteFactory->create();
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();

        if ($session->getStoreId()) {
            if ($session->getCurrencyId()){
                $quote->setQuoteCurrencyCode($session->getCurrencyId());
            }
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
     * @param CustomerInterface $customer
     * @param ModelQuote $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function fillCustomerData(CustomerInterface $customer, ModelQuote $quote)
    {
        $quoteData = [];
        if (
            !$quote->getCustomerId()
            && !$quote->getPayment()->getAdditionalInformation('customer_id')
            && $quote->getPayment()->getAdditionalInformation('public_hash')
            && $customer->getId()
        ) {
            $quote->getPayment()->setAdditionalInformation('customer_id', $customer->getId());
            $token = $this
                ->paymentTokenManagement
                ->getByPublicHash($quote->getPayment()->getAdditionalInformation('public_hash'), null);
            if ($token) {
                $token->setCustomerId($customer->getId());
                $this->paymentTokenRepository->save($token);
            }
        }
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
        foreach ($quoteData as $code => $value) {
            $quote->setData($code, $value);
        }
        $quote->setCustomer($customer);
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
     * @throws \Exception
     */
    public function validate(ModelQuote $quote)
    {
        $errors = [];
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();

        if (!$session->getStore()->getId()) {
            throw new \Exception(__('Please select a store'));
        }
        $items = $quote->getAllVisibleItems();

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

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->getContext()->log($error);
                $this->getContext()->getMessageManager()->addError($error);
            }
            //Maybe we need to delete customer in this case.
            throw new \Exception(__('Quote validation is failed.'));
        };
    }
}
