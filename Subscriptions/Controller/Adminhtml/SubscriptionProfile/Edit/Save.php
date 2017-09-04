<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Edit;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

class Save extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * Errors list
     *
     * @var array
     */
    private $errors = [];

    /**
     * @var  SubscriptionProfile
     */
    private $profile;
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var AddressInterfaceFactory
     */
    private $addressDataFactory;
    /**
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
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param PageFactory $resultPageFactory
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param JsonFactory $jsonFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressInterfaceFactory $addressDataFactory
     * @param FormFactory $customerForm
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        PageFactory $resultPageFactory,
        SubscriptionProfileRepositoryInterface $profileRepository,
        JsonFactory $jsonFactory,
        DataObjectHelper $dataObjectHelper,
        CustomerRepositoryInterface $customerRepository,
        AddressInterfaceFactory $addressDataFactory,
        FormFactory $customerForm
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->profileRepository = $profileRepository;
        $this->dataPersistor = $dataPersistor;
        $this->coreRegistry = $coreRegistry;
        $this->jsonFactory = $jsonFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerRepository = $customerRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->customerFormFactory = $customerForm;

        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->initProfile();

        if ($result) {
            $this->processRequestData();
            $this->saveProfile($this->getProfile());
        }

        $response = new DataObject();
        $response->setData('result', $result);

        return $this->jsonFactory->create()->setJsonData($response->toJson());
    }

    /**
     * Init subscription profile from Request
     *
     * @return bool
     */
    private function initProfile()
    {
        $result = true;
        $profileId = $this->getRequest()->getParam(SummaryInsertForm::FORM_DATA_KEY);
        /** @var SubscriptionProfile $model */
        $model = null;
        if ($profileId) {
            try {
                $model = $this->profileRepository->getById($profileId);
            } catch (\Exception $e) {
                $result = false;
            }
        }
        if ($model) {
            $this->profile = $model;
            $this->coreRegistry->register('tnw_subscription_profile', $model, true);
        }
        return $result;
    }

    /**
     * Saves Subscription Profile
     *
     * @param SubscriptionProfile $profile
     * @return $this
     */
    private function saveProfile(SubscriptionProfile $profile)
    {
        $this->profileRepository->save($profile);
        return $this;
    }

    /**
     * Returns Subscription profile
     *
     * @return SubscriptionProfile
     */
    private function getProfile()
    {
        return $this->profile;
    }

    /**
     * Process request data from request
     */
    private function processRequestData()
    {
        $requestData = $this->getRequest()->getParams();

        $this->processShippingAddress($requestData);
    }

    /**
     * Process shipping data from request
     *
     * @param $data
     */
    private function processShippingAddress($data)
    {
        if (!empty($data['shipping_address']) && !empty($data['shipping_info'])) {
            $address = array_merge($data['shipping_address'], $data['shipping_info']);
            $customerAddressId = !empty($address['customer_address_id'])
                ? $address['customer_address_id']
                : null;
            $saveAddress = isset($address['save_address']) && $address['save_address'];

            $address = $this->formatMultiLineAttributes($address);
            /** @var SubscriptionProfileAddressInterface $profileShippingAddress */
            $profileShippingAddress = $this->getProfileShippingAddress();

            $this->dataObjectHelper->populateWithArray(
                $profileShippingAddress,
                $address,
                SubscriptionProfileAddressInterface::class
            );

            if ($saveAddress) {
                $customerAddress = $profileShippingAddress->exportCustomerAddress();
                /** @var CustomerInterface $customer */
                $customer = $this->getProfile()->getCustomer();
                $addresses = (array)$customer->getAddresses();
                $addresses[] = $customerAddress;
                $customer->setAddresses($addresses);
                $this->saveCustomer($customer);
                $customerAddressId = $customerAddress->getId();
            }

            $profileShippingAddress->setCustomerAddressId($customerAddressId);
            $profileShippingAddress->setStreet('test');//TODO fix this
        }
    }

    /**
     * Returns profile's Shipping address
     *
     * @return mixed|null|SubscriptionProfileAddressInterface
     */
    private function getProfileShippingAddress()
    {
        return $this->getProfile()->getShippingAddress();
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
