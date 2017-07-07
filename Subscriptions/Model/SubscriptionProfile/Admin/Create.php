<?php

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory as ModelQuoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Backend\Session\Quote;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\Trial;
use Magento\Customer\Model\Metadata\Form;

class Create
{
    const SUBSCRIPTION_BUY_REQUEST_PARAM_NAME = 'subscription_data';

    /** @var Context */
    protected $context;

    /** @var Quote */
    protected $session;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $cartRepository;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var DataObject */
    protected $buyRequest;

    /** @var ModelQuoteFactory */
    protected $quoteFactory;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var GroupManagementInterface */
    protected $groupManagement;

    /** @var AddressFactory */
    protected $addressFactory;

    /** @var FormFactory */
    protected $formFactory;

    /**
     * Create constructor.
     * @param Context $context
     * @param Quote $session
     * @param StoreManagerInterface $storeManager
     * @param CartRepositoryInterface $cartRepository
     * @param ModelQuoteFactory $quoteFactory
     * @param AddressFactory $addressFactory
     * @param GroupManagementInterface $groupManagement
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param FormFactory $formFactory
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        Context $context,
        Quote $session,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $cartRepository,
        ModelQuoteFactory $quoteFactory,
        AddressFactory $addressFactory,
        GroupManagementInterface $groupManagement,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        FormFactory $formFactory,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->context = $context;
        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->cartRepository = $cartRepository;
        $this->quoteFactory = $quoteFactory;
        $this->addressFactory = $addressFactory;
        $this->groupManagement = $groupManagement;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->formFactory = $formFactory;
        $this->addressRepository = $addressRepository;
    }

    /**
     * @return DataObject
     */
    public function getBuyRequest()
    {
        return $this->buyRequest;
    }

    /**
     * @param [] $productData
     * @return array
     */
    public function addToSubscription($productData)
    {
        $this->buyRequest = null;

        $result = [];
        try {
            $product = $this->prepareProduct($productData);

            $this->prepareBuyRequest($productData);

            $quote = $this->getSubQuote();

            $quote->addProduct($product, $this->getBuyRequest());
            $quote->setTotalsCollectedFlag(false);
            $this->cartRepository->save($quote);

            $result['error'] = false;
        } catch (\Exception $e) {
            $this->context->log($e->getMessage());
            $result = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        return $result;
    }

    /**
     * @param $productId
     * @return ProductInterface
     */
    protected function getProduct($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * @return ModelQuote|null
     */
    protected function getSubQuote()
    {
        $result = null;

        $subQuotes = $this->session->getSubQuotes();

        $canAdd = false;
        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            if ($this->canAddProduct($subQuote)) {
                $canAdd = true;
                $result = $subQuote;
                break;
            }
        }

        if (!$canAdd) {
            $result = $this->createSubCart();
        }

        $this->session->addSubQuote($result);

        return $result;
    }

    /**
     * @param ModelQuote $subQuote
     * @return bool
     */
    protected function canAddProduct($subQuote)
    {
        $result = false;

        $quoteItems = $subQuote->getAllItems();
        /** @var Item $item */
        $item = $quoteItems ? reset($quoteItems) : null;

        if ($item) {
            $request = $item->getBuyRequest()->getData(self::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);

            $newRequest = $this->buyRequest->getData(self::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);

            if ($request == $newRequest) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @param [] $productData
     */
    protected function prepareBuyRequest($productData)
    {
        /** @var Product $product */
        $product = $this->getProduct($productData['product_id']);

        //Note: If product "is trial" then "start on" is start date of trial period,
        // otherwise "start on" is start date of subscription
        $data = [
            'qty' => $productData['qty'],
            self::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                'billing_frequency' => $productData['product_billing_frequency'],
                'term' => $productData['term'],
                'period' => $productData['period'],
                'is_trial' => $product->getData(Trial::CODE_TRIAL) ? true : false,
                'start_on' => $this->getStartOnDate($productData['start_on']),
            ],
        ];

        $this->buyRequest = new DataObject($data);
    }

    /**
     * @param $productData
     * @return ProductInterface
     */
    protected function prepareProduct($productData)
    {
        $product = $this->getProduct($productData['product_id']);

        $product->setPrice($productData['price']);

        return $product;
    }

    /**
     * @return int|string
     */
    protected function createSubCart()
    {
        /** @var ModelQuote $quote */
        $quote = $this->quoteFactory->create();

        if ($this->session->getStoreId()) {
            $quote->setCustomerGroupId($this->groupManagement->getDefaultGroup()->getId());
            $quote->setIsActive(false);
            $quote->setStoreId($this->session->getStoreId());

            if (!$this->session->getCustomerId()) {
                $quote->setBillingAddress($this->addressFactory->create());
                $quote->setShippingAddress($this->addressFactory->create());
            }

            $this->cartRepository->save($quote);
            $quote = $this->cartRepository->get($quote->getId(), [$this->session->getStoreId()]);

            if ($this->session->getCustomerId() &&
                $this->session->getCustomerId() != $quote->getCustomerId()
            ) {
                $customer = $this->customerRepository->getById($this->session->getCustomerId());
                $quote->assignCustomer($customer);
                $quote->setTotalsCollectedFlag(true);
                $this->cartRepository->save($quote);
            }
        }

        $quote->setIgnoreOldQty(true);
        $quote->setIsSuperMode(true);

        return $quote;
    }

    /**
     * @param $startOn
     * @return mixed
     */
    protected function getStartOnDate($startOn)
    {
        switch ($startOn) {
            case StartDateType::LAST_DAY_OF_THE_CURRENT_MONTH:
                $result = new \DateTime();
                $result = $result->format('Y-m-t');
                break;
            case StartDateType::MOMENT_OF_PURCHASE:
                $result = new \DateTime();
                $result = $result->format('Y-m-d');
                break;
            default:
                $result = new \DateTime($startOn);
                $result = $result->format('Y-m-d');
                break;
        }

        return $result;
    }

    /**
     * @param $address
     * @param null $customerAddressId
     * @return array
     */
    public function setShippingAddress($address, $customerAddressId = null)
    {
        $result = [];

        /** @var Address $shippingAddress */
        $shippingAddress = $this->getShippingAddress();

        $shippingAddress->setAddressType(Address::TYPE_SHIPPING);

        if ($customerAddressId) {
            $addressData = null;
            try {
                $addressData = $this->addressRepository->getById($customerAddressId);
            } catch (NoSuchEntityException $e) {
                // do nothing if customer is not found by id
            }

            if ($addressData->getCustomerId() != $this->session->getCustomerId()) {
                return [__('The customer address is not valid.')];
            }

            $result = $this->checkCustomerAddress($shippingAddress, $addressData);

        } elseif (is_array($address)) {
            $shippingAddress->setData($address);

            $result = $this->checkQuoteAddress($shippingAddress, $address);
        }

        //check if we have errors on address validation
        //may be make sense don't do this and always save address to quotes
        if ($result === true) {
            $saveInAddressBook = (int)(!empty($address['save_in_address_book']));

            $shippingAddress->setSaveInAddressBook($saveInAddressBook);
            $shippingAddress->setSameAsBilling(0);

            foreach ($this->session->getSubQuotes() as $subQuote) {
                $subQuote->setShippingAddress($shippingAddress);
                $this->cartRepository->save($subQuote);
            }
        }

        return $result;
    }

    /**
     * @param $address
     * @param null $customerAddressId
     * @return array
     */
    public function setBillingAddress($address, $customerAddressId = null)
    {
        $result = [];
        /** @var Address $shippingAddress */
        $billingAddress = $this->getBillingAddress();
        $billingAddress->setAddressType(Address::TYPE_BILLING);

        if ($customerAddressId) {
            $addressData = null;
            try {
                $addressData = $this->addressRepository->getById($customerAddressId);
            } catch (NoSuchEntityException $e) {
                // do nothing if customer is not found by id
            }

            if ($addressData->getCustomerId() != $this->session->getCustomerId()) {
                return [__('The customer address is not valid.')];
            }

            $result = $this->checkCustomerAddress($billingAddress, $addressData);

        } elseif (is_array($address)) {
            if ($address['same_as_shipping']) {
                $billingAddress = clone $this->getShippingAddress();
                $billingAddress->unsAddressId();
                $billingAddress->setAddressType(Address::TYPE_BILLING);
                $billingAddress->setSaveInAddressBook(0);
                $result = true;
            }else{
                $billingAddress->setData($address);
                $result = $this->checkQuoteAddress($billingAddress, $address);
            }
        }

        //check if we have errors on address validation
        //may be make sense don't do this and always save address to quotes
        if ($result === true) {
            $saveInAddressBook = (int)(!empty($address['save_in_address_book']));
            $billingAddress->setData('save_in_address_book', $saveInAddressBook);

            foreach ($this->session->getSubQuotes() as $subQuote) {
                $subQuote->setBillingAddress($billingAddress);
                $this->cartRepository->save($subQuote);
            }
        }

        return $result;
    }

    /**
     * @return Address
     */
    public function getShippingAddress()
    {
        $quotes = $this->session->getSubQuotes();

        if (!empty($quotes)) {
            $quote = reset($quotes);

            $result = $quote->getShippingAddress();
        } else {
            $result = $this->addressFactory->create();
        }

        return $result;
    }

    /**
     * @param Address $address
     * @param AddressInterface $customerAddressData
     * @return array|bool
     */
    protected function checkCustomerAddress($address, $customerAddressData)
    {
        $address->importCustomerAddressData($customerAddressData)->setSaveInAddressBook(0);

        $addressErrors = $this->getCustomerForm()->validateData($address->getData());

        return $addressErrors;
    }

    /**
     * @param Address $address
     * @param [] $data
     * @return array
     */
    protected function checkQuoteAddress(Address $address, array $data)
    {
        $result = [];

        $addressForm = $this->getCustomerForm();

        $request = $addressForm->prepareRequest($data);
        $addressData = $addressForm->extractData($request);

        $errors = $addressForm->validateData($addressData);

        if ($errors !== true) {

            if ($address->getAddressType() == Address::TYPE_SHIPPING) {
                $typeName = __('Shipping Address: ');
            } else {
                $typeName = __('Billing Address: ');
            }

            foreach ($errors as $error) {
                $result[] = $typeName . $error;
            }

        } else {
            $address->setData($addressForm->compactData($addressData));
        }

        return $result;
    }

    /**
     * @return Form
     */
    protected function getCustomerForm()
    {
        $addressForm = $this->formFactory->create(
            'customer_address',
            'adminhtml_customer_address',
            [],
            false,
            false,
            []
        );
        return $addressForm;
    }

    /**
     * @return Address
     */
    public function getBillingAddress()
    {
        $quotes = $this->session->getSubQuotes();

        if (!empty($quotes)) {
            $quote = reset($quotes);

            $result = $quote->getBillingAddress();
        } else {
            $result = $this->addressFactory->create();
        }

        return $result;
    }
}