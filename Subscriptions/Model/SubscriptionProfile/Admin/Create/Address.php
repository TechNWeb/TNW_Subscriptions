<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\AddressFactory;
use TNW\Subscriptions\Model\Backend\Session\Quote;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\AbstractCreate;
use Magento\Customer\Model\Metadata\Form;

/**
 * Class Address
 */
class Address extends AbstractCreate
{
    /**
     * Factory for creating addresses.
     *
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * Repository for retrieving addresses.
     *
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * Factory for creating customer metadata form.
     *
     * @var FormFactory
     */
    private $customerFormFactory;

    /**
     * Customer metadata form.
     *
     * @var Form
     */
    private $addressForm;

    /**
     * Address constructor.
     * @param Context $context
     * @param Quote $session
     * @param FormFactory $customerForm
     * @param AddressFactory $addressFactory
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        Context $context,
        Quote $session,
        FormFactory $customerForm,
        AddressFactory $addressFactory,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->customerFormFactory = $customerForm;
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        parent::__construct($context, $session);
    }


    /**
     * Validates and sets address by type to all subscription quotes.
     *
     * @param [] $address
     * @param string $addressType
     * @param null $customerAddressId
     * @return array|bool
     */
    public function setAddress($address, $addressType, $customerAddressId = null)
    {
        /** @var QuoteAddress $shippingAddress */
        $addressObject = $this->addressFactory->create();

        $addressObject->setAddressType($addressType);

        if ($customerAddressId) {
            $addressData = null;
            try {
                $addressData = $this->addressRepository->getById($customerAddressId);
            } catch (NoSuchEntityException $e) {
                // do nothing if customer is not found by id
            }

            if ($addressData->getCustomerId() !== $this->getSession()->getCustomerId()) {
                $result = [__('The customer address is not valid.')];
            } else {
                $result = $this->checkCustomerAddress($addressObject, $addressData);
            }

        } else {
            if ($addressType === QuoteAddress::ADDRESS_TYPE_BILLING && $address['same_as_shipping']) {
                $addressObject = clone $this->getAddress(QuoteAddress::ADDRESS_TYPE_SHIPPING);
                $addressObject->unsAddressId();
                $addressObject->setAddressType(QuoteAddress::ADDRESS_TYPE_BILLING);
                $addressObject->setSaveInAddressBook(false);
                $result = true;
            } else {
                $address = $this->formatMultiLineAttributes($address);
                $addressObject->setData($address);
                $addressObject->setAddressType($addressType);
                $result = $this->checkQuoteAddress($addressObject, $address);
            }
        }

        //check if we have errors on address validation
        //may be make sense don't do this and always save address to quotes
        if ($result === true) {
            $addressObject->setSaveInAddressBook(!empty($address['save_address']));

            foreach ($this->getSession()->getSubQuotes() as $subQuote) {
                if ($addressType === QuoteAddress::ADDRESS_TYPE_SHIPPING) {
                    $addressObject->setSameAsBilling(false);
                    $subQuote->setShippingAddress($addressObject);
                    $subQuote->getShippingAddress()->setCollectShippingRates(true);
                } else {
                    $subQuote->setBillingAddress($addressObject);
                }
            }
        }

        return $result;
    }

    /**
     * Returns shipping address from the first subscription quote or empty address object.
     *
     * @param string $type
     * @return QuoteAddress
     */
    public function getAddress($type = null)
    {
        $quotes = $this->getSession()->getSubQuotes();

        if (!empty($quotes)) {
            $quote = reset($quotes);

            if ($type === QuoteAddress::ADDRESS_TYPE_SHIPPING) {
                $result = $quote->getShippingAddress();
            } else {
                $result = $quote->getBillingAddress();
            }

        } else {
            $result = $this->addressFactory->create();
        }

        return $result;
    }

    /**
     * Returns emty address object.
     *
     * @return QuoteAddress
     */
    public function getEmptyAddress()
    {
        return $this->addressFactory->create();
    }

    /**
     * Imports customer address data into address nd validates it in customer form.
     *
     * @param QuoteAddress $address
     * @param AddressInterface $customerAddressData
     * @return array|bool
     */
    private function checkCustomerAddress($address, $customerAddressData)
    {
        $address->importCustomerAddressData($customerAddressData)->setSaveInAddressBook(0);

        return $this->getCustomerForm()->validateData($address->getData());
    }

    /**
     * Prepares and validates address data in customer form.
     *
     * @param QuoteAddress $address
     * @param [] $data
     * @return array|bool
     */
    private function checkQuoteAddress(QuoteAddress $address, array $data)
    {
        $result = true;

        $addressForm = $this->getCustomerForm();

        $request = $addressForm->prepareRequest($data);
        $addressData = $addressForm->extractData($request);

        $errors = $addressForm->validateData($addressData);

        if ($errors !== true) {

            if ($address->getAddressType() === QuoteAddress::TYPE_SHIPPING) {
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
    private function getCustomerForm()
    {
        if (!$this->addressForm) {
            $this->addressForm = $this->customerFormFactory->create(
                'customer_address',
                'adminhtml_customer_address',
                [],
                false,
                false
            );

        }

        return $this->addressForm;
    }

    /**
     * Format multiline attributes for validation and save into the database.
     *
     * @param array $address
     * @return array
     */
    private function formatMultiLineAttributes($address)
    {
        $addressForm = $this->getCustomerForm();
        $allowedAttributes = $addressForm->getAllowedAttributes();

        /** @var \Magento\Customer\Api\Data\AttributeMetadataInterface $attribute */
        foreach ($allowedAttributes as $attributeCode => $attribute) {
            if ($attribute->getFrontendInput() == 'multiline') {
                foreach ($address as $key => $addressValue) {
                    if (stripos($key, $attributeCode) !== false) {
                        $attributeKey = explode($attributeCode, $key)[1];
                        $address[$attributeCode][$attributeKey] = $addressValue;
                        unset($address[$key]);
                    }
                }
            }
        }

        return $address;
    }
}