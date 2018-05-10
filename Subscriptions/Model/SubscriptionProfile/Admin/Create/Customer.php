<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Quote\Model\Quote as MagentoQuote;
use Magento\Store\Model\Store;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;

/**
 * Class Customer
 */
class Customer extends Create
{
    /**
     * Repository for saving/retrieving customers.
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Customers accounts manager.
     *
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * Repository for retrieving customer addresses.
     *
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * Data object helper.
     *
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerDataFactory;

    /**
     * Customer constructor.
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param AddressRepositoryInterface $addressRepository
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        AddressRepositoryInterface $addressRepository,
        DataObjectHelper $dataObjectHelper,
        CustomerInterfaceFactory $customerDataFactory
    ) {
        $this->customerRepository = $customerRepository;
        $this->accountManagement = $accountManagement;
        $this->addressRepository = $addressRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerDataFactory = $customerDataFactory;
        parent::__construct($context, $session);
    }

    /**
     * @return CustomerInterface
     */
    public function prepareCustomer()
    {
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        /** @var Store $store */
        $store = $session->getStore();
        $customer = $this->getCustomer();

        if ($customer->getId()) {
            if (!$this->customerIsInStore($store)) {
                /** Create a new customer record if it is not available in the specified store */
                $customer->setId(null)
                    ->setStoreId($store->getId())
                    ->setWebsiteId($store->getWebsiteId())
                    ->setCreatedAt(null);
                $customer = $this->validateCustomerData($customer);
            }
        } else {
            /** Create new customer */
            $customer = $this->validateCustomerData(
                $this->getNewCustomer($customer, $store)
            );
            $customer = $this->accountManagement->createAccountWithPasswordHash(
                $customer,
                null
            );
        }

        $alreadySaveBilling = false;
        $alreadySaveShipping = false;

        foreach ($session->getSubQuotes() as $subQuote) {

            if ($subQuote->getBillingAddress()->getSaveInAddressBook()) {
                // save only first billing address, because billing address is the same for all subscription quotes
                if (!$alreadySaveBilling) {
                    $customer = $this->saveCustomerAddress($customer, $subQuote->getBillingAddress());
                    $alreadySaveBilling = true;
                }
            }
            $address = $subQuote->getBillingAddress()->setCustomerId($customer->getId());
            $address->setSaveInAddressBook(false);
            $subQuote->setBillingAddress($address);

            if (!$subQuote->isVirtual() && $subQuote->getShippingAddress()->getSaveInAddressBook()) {
                // save only first shipping address, because shipping address is the same for all subscription quotes
                if (!$alreadySaveShipping) {
                    $customer = $this->saveCustomerAddress($customer, $subQuote->getShippingAddress());
                    $subQuote->getShippingAddress()->setSaveInAddressBook(false);
                    $alreadySaveShipping = true;
                }
            }elseif (!$subQuote->isVirtual()){
                $address = $subQuote->getShippingAddress()->setCustomerId($customer->getId());
                $address->setSaveInAddressBook(false);
                $subQuote->setShippingAddress($address);
            }
        }

        return $customer;
    }

    /**
     * Check whether we need to create new customer (for another website) during order creation.
     *
     * @param Store $store
     * @return bool
     */
    private function customerIsInStore(Store $store)
    {
        $websiteId = $this->getCustomer()->getWebsiteId();

        return $websiteId == $store->getWebsiteId()
            || $this->accountManagement->isCustomerInStore($websiteId, $store->getId());
    }

    /**
     *
     * Validates customer data.
     *
     * @param CustomerInterface $customer
     * @return CustomerInterface
     * @throws \Exception
     */
    private function validateCustomerData(CustomerInterface $customer)
    {
        $validationResults = $this->accountManagement->validate($customer);
        if (!$validationResults->isValid()) {
            $errors = $validationResults->getMessages();
            if (is_array($errors)) {
                foreach ($errors as $error) {
                    $this->getContext()->log($error);
                    $this->getContext()->getMessageManager()->addError($error);
                }
                throw new \Exception(__('Customer validation failed.'));
            }
        }

        return $customer;
    }

    /**
     * Create customer address and save it.
     *
     * @param CustomerInterface $customer
     * @param Address $quoteCustomerAddress
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function saveCustomerAddress(
        CustomerInterface $customer,
        MagentoQuote\Address $quoteCustomerAddress
    ) {
        // Possible that customerId is null for new customers
        $quoteCustomerAddress->setCustomerId($customer->getId());
        $customerAddress = $quoteCustomerAddress->exportCustomerAddress();
        $quoteAddressId = $quoteCustomerAddress->getCustomerAddressId();
        $addressType = $quoteCustomerAddress->getAddressType();
        if ($quoteAddressId) {
            /** Update existing address */
            $existingAddressDataObject = $this->addressRepository->getById($quoteAddressId);
            /** Update customer address data */
            $this->dataObjectHelper->mergeDataObjects(
                get_class($existingAddressDataObject),
                $existingAddressDataObject,
                $customerAddress
            );
            $customerAddress = $existingAddressDataObject;
        }

        switch ($addressType) {
            case MagentoQuote\Address::ADDRESS_TYPE_BILLING:
                if (is_null($customer->getDefaultBilling())) {
                    $customerAddress->setIsDefaultBilling(true);
                }
                break;
            case MagentoQuote\Address::ADDRESS_TYPE_SHIPPING:
                if (is_null($customer->getDefaultShipping())) {
                    $customerAddress->setIsDefaultShipping(true);
                }
                break;
            default:
                throw new \InvalidArgumentException('Customer address type is invalid.');
        }
        //save address to customer.
        $addresses = (array)$customer->getAddresses();
        $addresses[] = $customerAddress;
        $customer->setAddresses($addresses);
        return $this->customerRepository->save($customer);
    }

    /**
     * Returns customer by id.
     *
     * @param int|null $customerId
     * @return CustomerInterface
     */
    public function getCustomer($customerId = null)
    {
        $customer = null;
        if (!$customerId) {
            /** @var QuoteSessionInterface $session */
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
        }

        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
        } else {
            $customer = $this->customerDataFactory->create();
        }

        return $customer;
    }

    /**
     * Populates customer data from shipping address.
     *
     * @param CustomerInterface $customer
     * @param Store $store
     * @return mixed
     */
    private function getNewCustomer(
        CustomerInterface $customer,
        Store $store
    ) {
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        $shippingAddress = $session->getFirstQuote()->getShippingAddress();
        $customerAddressObject = $shippingAddress->exportCustomerAddress();
        $groupId = $session->getCustomerGroup() ?: $session->getFirstQuote()->getCustomerGroupId();
        $customer->setSuffix($customerAddressObject->getSuffix())
            ->setFirstname($customerAddressObject->getFirstname())
            ->setLastname($customerAddressObject->getLastname())
            ->setMiddlename($customerAddressObject->getMiddlename())
            ->setPrefix($customerAddressObject->getPrefix())
            ->setStoreId($store->getId())
            ->setWebsiteId($store->getWebsiteId())
            ->setEmail($session->getCustomerEmail())
            ->setGroupId($groupId);

        return $customer;
    }
}
