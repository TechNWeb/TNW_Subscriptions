<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Model\AbstractModel;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Address as ResourceAddress;

/**
 * Class Address
 */
class Address extends AbstractModel implements SubscriptionProfileAddressInterface
{
    const SUBSCRIPTION_PROFILE_ADDRESS_TABLE = 'tnw_subscriptions_subscription_profile_address';

    /**
     * Copy service
     *
     * @var Copy
     */
    private $objectCopyService;

    /**
     * Address interface
     *
     * @var AddressInterfaceFactory
     */
    private $addressDataFactory;

    /**
     * Data object helper
     *
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger
     */
    private $historyLogger;

    /**
     * @var array
     */
    protected $logField = [
        self::FIRSTNAME => 'First Name',
        self::MIDDLENAME => 'Middle Name',
        self::LASTNAME => 'Last Name',
        self::SUFFIX => 'Suffix Name',
        self::COMPANY => 'Company',
        self::STREET => 'Street',
        self::CITY => 'City',
        self::REGION => 'Region',
        self::POSTCODE => 'Postcode',
        self::COUNTRY_ID => 'Country',
        self::TELEPHONE => 'Telephone',
        self::FAX => 'Fax'
    ];

    /**
     * Address constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Copy $objectCopyService
     * @param AddressInterfaceFactory $addressDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger $historyLogger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Copy $objectCopyService,
        AddressInterfaceFactory $addressDataFactory,
        DataObjectHelper $dataObjectHelper,
        MessageHistoryLogger $historyLogger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->objectCopyService = $objectCopyService;
        $this->addressDataFactory = $addressDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->historyLogger = $historyLogger;
    }

    /**
     * Export data to customer address Data Object.
     *
     * @return AddressInterface
     */
    public function exportCustomerAddress()
    {
        $customerAddressData = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_quote_address',
            'to_customer_address',
            $this
        );
        $customerAddressDataWithRegion = [];
        $customerAddressDataWithRegion['region']['region'] = $customerAddressData['region'];
        if (isset($customerAddressData['region_code'])) {
            $customerAddressDataWithRegion['region']['region_code'] = $customerAddressData['region_code'];
        }
        if (isset($customerAddressData['region_id'])) {
            $customerAddressDataWithRegion['region']['region_id'] = $customerAddressData['region_id'];
        }
        $customerAddressData = array_merge($customerAddressData, $customerAddressDataWithRegion);

        if (!isset($customerAddressData['street'])) {
            $customerAddressData['street'] = [];
        }

        if (isset($customerAddressData['street']) && !is_array($customerAddressData['street'])) {
            $customerAddressData['street'] = explode("\n", $customerAddressData['street']);
        }

        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            $customerAddressData,
            AddressInterface::class
        );
        return $addressDataObject;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceAddress::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddressType()
    {
        return $this->getData(static::ADDRESS_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setAddressType($addressType)
    {
        return $this->setData(self::ADDRESS_TYPE, $addressType);
    }

    /**
     * {@inheritdoc}
     */
    public function getCity()
    {
        return $this->getData(static::CITY);
    }

    /**
     * {@inheritdoc}
     */
    public function setCity($city)
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompany()
    {
        return $this->getData(static::COMPANY);
    }

    /**
     * {@inheritdoc}
     */
    public function setCompany($company)
    {
        return $this->setData(self::COMPANY, $company);
    }

    /**
     * {@inheritdoc}
     */
    public function getCountryId()
    {
        return $this->getData(static::COUNTRY_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCountryId($id)
    {
        return $this->setData(self::COUNTRY_ID, $id);
    }

    /**
     * Gets customer address id.
     *
     * @return string
     */
    public function getCustomerAddressId()
    {
        return $this->getData(static::CUSTOMER_ADDRESS_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerAddressId($id)
    {
        return $this->setData(self::CUSTOMER_ADDRESS_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData(static::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($addressId)
    {
        return $this->setData(self::ID, $addressId);
    }

    /**
     * {@inheritdoc}
     */
    public function getFax()
    {
        return $this->getData(static::FAX);
    }

    /**
     * {@inheritdoc}
     */
    public function setFax($fax)
    {
        return $this->setData(self::FAX, $fax);
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstname()
    {
        return $this->getData(static::FIRSTNAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstname($firstname)
    {
        return $this->setData(self::FIRSTNAME, $firstname);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastname()
    {
        return $this->getData(static::LASTNAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setLastname($lastname)
    {
        return $this->setData(self::LASTNAME, $lastname);
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlename()
    {
        return $this->getData(static::MIDDLENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setMiddlename($middlename)
    {
        return $this->setData(self::MIDDLENAME, $middlename);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileId()
    {
        return $this->getData(static::PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setProfileId($id)
    {
        return $this->setData(self::PROFILE_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getPostcode()
    {
        return $this->getData(static::POSTCODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setPostcode($postcode)
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix()
    {
        return $this->getData(static::PREFIX);
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefix($prefix)
    {
        return $this->setData(self::PREFIX, $prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegion()
    {
        return $this->getData(static::REGION);
    }

    /**
     * {@inheritdoc}
     */
    public function setRegion($region)
    {
        return $this->setData(self::REGION, $region);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegionId()
    {
        return $this->getData(static::REGION_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setRegionId($id)
    {
        return $this->setData(self::REGION_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStreet()
    {
        return $this->getData(static::STREET);
    }

    /**
     * {@inheritdoc}
     */
    public function setStreet($street)
    {
        return $this->setData(self::STREET, $street);
    }

    /**
     * {@inheritdoc}
     */
    public function getSuffix()
    {
        return $this->getData(static::SUFFIX);
    }

    /**
     * {@inheritdoc}
     */
    public function setSuffix($suffix)
    {
        return $this->setData(self::SUFFIX, $suffix);
    }

    /**
     * {@inheritdoc}
     */
    public function getTelephone()
    {
        return $this->getData(static::TELEPHONE);
    }

    /**
     * {@inheritdoc}
     */
    public function setTelephone($telephone)
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave()
    {
        foreach ($this->logField as $field => $fieldName) {
            if ($field === self::STREET && is_array($this->getOrigData($field))) {
                $this->setOrigData($field, implode("\n", $this->getOrigData($field)));
            }

            if (!$this->dataHasChangedFor($field)) {
                continue;
            }

            $type = $this->getAddressType() == self::ADDRESS_TYPE_SHIPPING
                ? __('Shipping Address') : __('Billing Address');

            $oldValue = (string)$this->getOrigData($field);
            $newValue = (string)$this->getData($field);

            if (!$this->getOrigData($field)) {
                $message = __(
                    '%1 %2 changed to <b>%3</b>',
                    $type,
                    $fieldName,
                    $newValue
                );
            } else {
                $message = __(
                    '%1 %2 changed from  <b>%3</b> to <b>%4</b>',
                    $type,
                    $fieldName,
                    $oldValue,
                    $newValue
                );
            }

            $this->historyLogger->log($message, $this->getProfileId());
        }

        return parent::afterSave();
    }
}
