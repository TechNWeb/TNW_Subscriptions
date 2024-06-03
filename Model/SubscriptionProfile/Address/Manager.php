<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Address;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Model\Metadata\Form;
use Magento\Customer\Model\Address\Mapper as AddressMapper;
use Magento\Framework\Api\DataObjectHelper;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;

/**
 * Class Manager - address manager model
 */
class Manager
{
    /**
     * Customer repository
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Customer interface
     *
     * @var AddressInterfaceFactory
     */
    private $addressDataFactory;

    /**
     * Customer from
     *
     * @var FormFactory
     */
    private $customerForm;

    /**
     * Profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * Customer metadata form.
     *
     * @var Form
     */
    private $addressForm;

    /**
     * Address mapper
     *
     * @var AddressMapper
     */
    private $addressMapper;

    /**
     * @var DataObjectHelper
     */
    private DataObjectHelper $dataObjectHelper;

    /**
     * Manager constructor.
     * @param DataObjectHelper $dataObjectHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressInterfaceFactory $addressDataFactory
     * @param FormFactory $customerForm
     * @param ProfileManager $profileManager
     * @param AddressMapper $addressMapper
     */
    public function __construct(
        DataObjectHelper $dataObjectHelper,
        CustomerRepositoryInterface $customerRepository,
        AddressInterfaceFactory $addressDataFactory,
        FormFactory $customerForm,
        ProfileManager $profileManager,
        AddressMapper $addressMapper
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerRepository = $customerRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->customerForm = $customerForm;
        $this->profileManager = $profileManager;
        $this->addressMapper = $addressMapper;
    }

    /**
     * Process shipping data from request
     *
     * @param $data
     */
    public function processShippingAddress($data)
    {
        $this->processAddress($data, SubscriptionProfileAddressInterface::ADDRESS_TYPE_SHIPPING);
    }

    /**
     * Process billing data from request
     *
     * @param $data
     */
    public function processBillingAddress($data)
    {
        $this->processAddress($data, SubscriptionProfileAddressInterface::ADDRESS_TYPE_BILLING);
    }

    /**
     * Process billing/shipping data from request
     *
     * @param $data
     * @param $type
     */
    public function processAddress($data, $type)
    {
        if ($type === SubscriptionProfileAddressInterface::ADDRESS_TYPE_SHIPPING) {
            $keyAddress = 'shipping_address';
            $keyInfo = 'shipping_info';
            $keyCustomer = 'customer_shipping_address_id';
        } else {
            $keyAddress = 'billing_address';
            $keyInfo = 'billing_info';
            $keyCustomer = 'customer_billing_address_id';
        }
        if (!empty($data[$keyAddress]) && !empty($data[$keyInfo])) {
            $address = array_merge($data[$keyAddress], $data[$keyInfo]);
            $customerAddressId = !empty($address[$keyCustomer])
                ? (int)$address[$keyCustomer]
                : null;
            $saveAddress = isset($address['save_address']) && $address['save_address'];
            /** @var \TNW\Subscriptions\Model\SubscriptionProfile\Address $profileAddress */
            $profileAddress = $this->getProfileAddress($type);
            $profileAddress->setOrigData();
            if ($customerAddressId) {
                /** @var CustomerInterface $customer */
                list(, $addresses) = $this->getCustomerAddresses();
                $address = null;
                if (is_array($addresses) && count($addresses) > 0) {
                    /** @var \Magento\Customer\Api\Data\AddressInterface $curAddress */
                    foreach ($addresses as $curAddress) {
                        if ((int)$curAddress->getId() === $customerAddressId) {
                            /** @var array $addressData */
                            $address = $this->addressMapper->toFlatArray($curAddress);
                            unset($address['id']);
                            break;
                        }
                    }
                }
            } else {
                $address = $this->formatMultiLineAttributes($address);
            }

            $addressDataChanges = $profileAddress->hasDataChanges();
            $profileAddress->setDataChanges(false);

            if ($address) {
                $address = $this->normalizeAddressData($address);
                $address = array_intersect_key($address, $profileAddress->getData());
                $this->dataObjectHelper->populateWithArray(
                    $profileAddress,
                    $address,
                    SubscriptionProfileAddressInterface::class
                );
            }

            if ($saveAddress) {
                $customerAddress = $profileAddress->exportCustomerAddress();
                list($customer, $addresses) = $this->getCustomerAddresses();
                $addresses[] = $customerAddress;
                if ($customer) {
                    $customer->setAddresses($addresses);
                    $this->saveCustomer($customer);
                    $customerAddressId = $customerAddress->getId();
                }
            }

            $profileAddress->setCustomerAddressId((string)$customerAddressId);
            if ($profileAddress->hasDataChanges()) {
                $this->profileManager->getProfile()->setNeedRecollect('1');
            }
            $profileAddress->setDataChanges($addressDataChanges || $profileAddress->hasDataChanges());
        }
    }

    /**
     * Returns profile's Shipping address
     *
     * @return SubscriptionProfileAddressInterface
     */
    private function getProfileAddress($type)
    {
        /** @var SubscriptionProfileAddressInterface $address */
        if ($type === SubscriptionProfileAddressInterface::ADDRESS_TYPE_SHIPPING) {
            $address = $this->profileManager->getProfile()->getShippingAddress();
        } else {
            $address = $this->profileManager->getProfile()->getBillingAddress();
        }
        return $address;
    }

    /**
     * Saves customer
     *
     * @param CustomerInterface $customer
     * @return $this
     */
    private function saveCustomer(CustomerInterface $customer)
    {
        $this->customerRepository->save($customer);
        return $this;
    }

    /**
     * Returns customer form. It is needed for address data validation.
     *
     * @return Form
     */
    private function getCustomerForm()
    {
        if (!$this->addressForm) {
            $this->addressForm = $this->customerForm->create(
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
            if ($attribute->getFrontendInput() === 'multiline') {
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

    /**
     * @return array
     */
    private function getCustomerAddresses()
    {
        /** @var CustomerInterface $customer */
        $customer = $this->profileManager->getProfile()->getCustomer();
        $addresses = [];
        if ($customer) {
            $addresses = (array) $customer->getAddresses();
        }
        return [$customer, $addresses];
    }

    /**
     * Normalizes address data. Replaces empty strings with null. Converts int to string. As they holds in object.
     *
     * @param $address
     * @return mixed
     */
    private function normalizeAddressData($address)
    {
        foreach ($address as $key => $item) {
            if ($item === '') {
                $address[$key] = null;
            }
            if (is_int($item)) {
                $address[$key] = (string)$item;
            }
        }
        return $address;
    }
}
