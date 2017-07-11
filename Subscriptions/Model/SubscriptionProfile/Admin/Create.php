<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Metadata\Form;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory as ModelQuoteFactory;
use TNW\Subscriptions\Model\Backend\Session\Quote;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\Trial;

/**
 * Class for creating subscriptions in admin.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * todo: refactor this class when it's finished to reduce coupling between objects.
 */
class Create
{
    /**
     * First part of path to subscription fields.
     */
    const SUBSCRIPTION_BUY_REQUEST_PARAM_NAME = 'subscription_data';

    /**
     * Last part off path to unique subscription fields in product buy request.
     *
     * Using for checking the ability to add product to subscription quote.
     */
    const UNIQUE = '/unique';

    /**
     * Last part off path to non_unique fields in product buy request.
     */
    const NON_UNIQUE = '/non_unique';

    /**
     * @var Context
     */
    private $context;

    /**
     * Session.
     *
     * @var Quote
     */
    private $session;

    /**
     * Repository for retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * Repository for retrieving products.
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Buy request onject
     *
     * @var DataObject
     */
    private $buyRequest;

    /**
     * Factory for creating quotes.
     *
     * @var ModelQuoteFactory
     */
    private $quoteFactory;

    /**
     * Repository for retrieving customers.
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Customer groups manager
     *
     * @var GroupManagementInterface
     */
    private $groupManagement;

    /**
     * Factory for creating addresses.
     *
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * Factory for creating customer metadata form.
     *
     * @var FormFactory
     */
    private $formFactory;

    /**
     * Repository for retrieving addresses.
     *
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * Help retrieve calculated product price.
     *
     * @var PriceCalculator
     */
    private $priceCalculator;

    /**
     * Create constructor.
     * @param Context $context
     * @param Quote $session
     * @param CartRepositoryInterface $cartRepository
     * @param ModelQuoteFactory $quoteFactory
     * @param AddressFactory $addressFactory
     * @param GroupManagementInterface $groupManagement
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param FormFactory $formFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param PriceCalculator $priceCalculator
     */
    public function __construct(
        Context $context,
        Quote $session,
        CartRepositoryInterface $cartRepository,
        ModelQuoteFactory $quoteFactory,
        AddressFactory $addressFactory,
        GroupManagementInterface $groupManagement,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        FormFactory $formFactory,
        AddressRepositoryInterface $addressRepository,
        PriceCalculator $priceCalculator
    ) {
        $this->context = $context;
        $this->session = $session;
        $this->cartRepository = $cartRepository;
        $this->quoteFactory = $quoteFactory;
        $this->addressFactory = $addressFactory;
        $this->groupManagement = $groupManagement;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->formFactory = $formFactory;
        $this->addressRepository = $addressRepository;
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * Returns product buy request.
     *
     * @return DataObject
     */
    public function getBuyRequest()
    {
        return $this->buyRequest;
    }

    /**
     * Adds product into new or already existing subscription quote.
     *
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
     * Returns product object.
     *
     * @param int $productId
     * @return ProductInterface
     */
    protected function getProduct($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * Returns new or already existing quote for adding in to it requested product.
     *
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
     * Checks whether it is possible to add a product in to quote.
     *
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
            $request = $item->getBuyRequest()->getDataByPath(self::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . self::UNIQUE);

            $newRequest = $this->buyRequest->getDataByPath(self::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . self::UNIQUE);

            if ($request == $newRequest) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Prepares product buy request.
     *
     * @param [] $productData
     * @return void
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
                'unique' => [
                    'billing_frequency' => $productData['product_billing_frequency'],
                    'term' => $productData['term'],
                    'period' => $productData['period'],
                    'is_trial' => $product->getData(Trial::CODE_TRIAL) ? true : false,
                    'start_on' => $this->getStartOnDate($productData['start_on']),
                ],
                'non_unique' => [
                    'price' => $this->priceCalculator->getUnitPrice(
                        $product->getId(),
                        $productData['product_billing_frequency']
                    )
                ]
            ],
        ];

        $this->buyRequest = new DataObject($data);
    }

    /**
     * Prepares product to adding product in to quote.
     *
     * @param $productData
     * @return ProductInterface
     */
    protected function prepareProduct($productData)
    {
        $product = $this->getProduct($productData['product_id']);

        $price = $this->priceCalculator->getUnitPrice(
            $product->getId(),
            $productData['product_billing_frequency'],
            true
        );
        $product->setPrice($price);

        return $product;
    }

    /**
     * Creates empty quote and assigns customer if there is a customer id in session.
     *
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
                $this->session->getCustomerId() !== $quote->getCustomerId()
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
     * Calculates start date for subscription.
     *
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
     * Validates and sets shipping address to all subscription quotes.
     *
     * @param $address
     * @param null $customerAddressId
     * @return array
     */
    public function setShippingAddress($address, $customerAddressId = null)
    {
        $result = [];

        /** @var Address $shippingAddress */
        $shippingAddress = $this->addressFactory->create();

        $shippingAddress->setAddressType(Address::TYPE_SHIPPING);

        if ($customerAddressId) {
            $addressData = null;
            try {
                $addressData = $this->addressRepository->getById($customerAddressId);
            } catch (NoSuchEntityException $e) {
                // do nothing if customer is not found by id
            }

            if ($addressData->getCustomerId() !== $this->session->getCustomerId()) {
                $result = [__('The customer address is not valid.')];
            } else {
                $result = $this->checkCustomerAddress($shippingAddress, $addressData);
            }

        } elseif (is_array($address)) {
            $shippingAddress->setData($address);

            $result = $this->checkQuoteAddress($shippingAddress, $address);
        }

        //check if we have errors on address validation
        //may be make sense don't do this and always save address to quotes
        if ($result === true) {

            if (!empty($address['save_in_address_book'])) {
                $shippingAddress->setSaveInAddressBook($address['save_in_address_book']);
            }

            $shippingAddress->setSameAsBilling(0);

            foreach ($this->session->getSubQuotes() as $subQuote) {
                $subQuote->setShippingAddress($shippingAddress);
                $subQuote->setTotalsCollectedFlag(false);
                $subQuote->getShippingAddress()->setCollectShippingRates(true);
                $this->cartRepository->save($subQuote);
            }
        }

        return $result;
    }

    /**
     * Validates and sets billing address to all subscription quotes.
     *
     * @param $address
     * @param null $customerAddressId
     * @return array|bool
     */
    public function setBillingAddress($address, $customerAddressId = null)
    {
        $result = [];

        /** @var Address $shippingAddress */
        $billingAddress = $this->addressFactory->create();
        $billingAddress->setAddressType(Address::TYPE_BILLING);

        if ($customerAddressId) {
            $addressData = null;

            try {
                $addressData = $this->addressRepository->getById($customerAddressId);
            } catch (NoSuchEntityException $e) {
                // do nothing if customer is not found by id
            }

            if ($addressData->getCustomerId() !== $this->session->getCustomerId()) {
                $result = [__('The customer address is not valid.')];
            } else {
                $result = $this->checkCustomerAddress($billingAddress, $addressData);
            }

        } elseif (is_array($address)) {
            if ($address['same_as_shipping']) {
                $billingAddress = clone $this->getShippingAddress();
                $billingAddress->unsAddressId();
                $billingAddress->setAddressType(Address::TYPE_BILLING);
                $billingAddress->setSaveInAddressBook(0);
                $result = true;
            } else {
                $billingAddress->setData($address);
                $result = $this->checkQuoteAddress($billingAddress, $address);
            }
        }

        //check if we have errors on address validation
        //may be make sense don't do this and always save address to quotes
        if ($result === true) {

            if (!empty($address['save_in_address_book'])) {
                $billingAddress->setSaveInAddressBook($address['save_in_address_book']);
            }

            foreach ($this->session->getSubQuotes() as $subQuote) {
                $subQuote->setBillingAddress($billingAddress);
                $this->cartRepository->save($subQuote);
            }
        }

        return $result;
    }

    /**
     * Returns shipping address from the first subscription quote or empty address object.
     *
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
     * Imports customer address data into address nd validates it in customer form.
     *
     * @param Address $address
     * @param AddressInterface $customerAddressData
     * @return array|bool
     */
    protected function checkCustomerAddress($address, $customerAddressData)
    {
        $address->importCustomerAddressData($customerAddressData)->setSaveInAddressBook(0);

        return $this->getCustomerForm()->validateData($address->getData());
    }

    /**
     * Prepares and validates address data in customer form.
     *
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

            if ($address->getAddressType() === Address::TYPE_SHIPPING) {
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
     * Returns customer form. It is needed for address data validation.
     *
     * @return Form
     */
    protected function getCustomerForm()
    {
        $addressForm = $this->formFactory->create(
            'customer_address',
            'adminhtml_customer_address',
            [],
            false,
            false
        );

        return $addressForm;
    }

    /**
     * Returns billing address from the first subscription quote or empty address object.
     *
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

    /**
     * Sets into subscription quotes shipping methods.
     *
     * @param $methods
     * @return array
     */
    public function setShippingMethods($methods)
    {
        $result = [];

        $subQuotes = $this->session->getSubQuotes();

        foreach ($subQuotes as $subQuote) {

            /** @var string|null $method */
            $method = !empty($methods[$subQuote->getId()]) ? $methods[$subQuote->getId()] : null;

            if ($method) {
                try {
                    $subQuote->getShippingAddress()->setShippingMethod($method);
                    $subQuote->getShippingAddress()->setCollectShippingRates(true);
                    $this->cartRepository->save($subQuote);
                } catch ( \Exception $e) {
                   $this->context->log($e->getMessage());

                   $result[] = __('Can not set shipping method - ' . $method . ' to quote with id - ' . $subQuote->getId());
                }

            } else {
                $result[] = __("Shipping method is required and can't be empty.");
            }
        }

        return $result;
    }
}
