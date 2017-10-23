<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\SubscriptionProfileAttributeRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as Resource;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Json\Helper\Data;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Subscription Profile model.
 */
class SubscriptionProfile extends AbstractExtensibleModel implements SubscriptionProfileInterface
{
    /**
     * Entity for subscription profile.
     */
    const SUBSCRIPTION_PROFILE_ENTITY = 'tnw_subscriptions_subscription_profile_entity';

    /**
     * Entity code.
     */
    const ENTITY = 'subscription_profile';

    /**
     * Default group code for custom attributes.
     */
    const DEFAULT_GROUP_CODE = 'additional-information';

    /**
     * Edit state code
     */
    const STATE_EDIT = 'edit';

    /**
     * Create state code
     */
    const STATE_CREATE = 'create';

    /**
     * Repository for retrieving customers.
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Repository for retrieving websites.
     *
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * Profile customer.
     *
     * @var CustomerInterface
     */
    private $customer;

    /**
     * Profile website.
     *
     * @var WebsiteInterface
     */
    private $website;

    /**
     * Provides basic logic for hashing strings.
     *
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * JSON helper.
     *
     * @var Data
     */
    private $jsonHelper;

    /**
     * @var SubscriptionProfileAttributeRepositoryInterface
     */
    private $metadataService;

    /**
     * Attributes are that part of interface
     *
     * @var array
     */
    protected $interfaceAttributes = [
        self::WEBSITE_ID,
        self::UNIT,
        self::CUSTOMER_ID,
        self::STATUS,
        self::FREQUENCY,
        self::ID,
        self::LABEL,
        self::BILLING_FREQUENCY_ID,
        self::ENGINE_CODE,
        self::START_DATE,
        self::TRIAL_START_DATE,
        self::TERM,
        self::TOTAL_BILLING_CYCLES,
        self::SHIPPING_METHOD,
        self::SHIPPING_DESCRIPTION,
        self::PROFILE_CURRENCY_CODE,
        self::TRIAL_LENGTH,
        self::TRIAL_LENGTH_UNIT,
        self::IS_VIRTUAL,
        self::TOKEN_HASH,
        self::PAYMENT_ADDITIONAL_INFO,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::GENERATE_QUOTES_STATE,
        self::NEED_RECOLLECT,
    ];

    /**
     * SubscriptionProfile constructor.
     * @param ModelContext $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param EncryptorInterface $encryptor
     * @param SubscriptionProfileAttributeRepositoryInterface $metadataService
     * @param Data $jsonHelper
     * @param Resource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ModelContext $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        CustomerRepositoryInterface $customerRepository,
        WebsiteRepositoryInterface $websiteRepository,
        EncryptorInterface $encryptor,
        SubscriptionProfileAttributeRepositoryInterface $metadataService,
        Data $jsonHelper,
        Resource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->websiteRepository = $websiteRepository;
        $this->encryptor = $encryptor;
        $this->jsonHelper = $jsonHelper;
        $this->metadataService = $metadataService;
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory,
            $resource, $resourceCollection, $data);
    }


    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(Resource::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomAttributesCodes()
    {
        if ($this->customAttributesCodes === null) {
            $this->customAttributesCodes = $this->getEavAttributesCodes($this->metadataService);
            $this->customAttributesCodes = array_diff($this->customAttributesCodes, $this->interfaceAttributes);
        }
        return $this->customAttributesCodes;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * {@inheritdoc}
     */
    public function getBillingFrequencyId()
    {
        return $this->getData(self::BILLING_FREQUENCY_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setBillingFrequencyId($billingFrequencyId)
    {
        return $this->setData(self::BILLING_FREQUENCY_ID,
            $billingFrequencyId);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return self::LABEL_PREFIX . $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnit()
    {
        return $this->getData(self::UNIT);
    }

    /**
     * {@inheritdoc}
     */
    public function setUnit($unit)
    {
        return $this->setData(self::UNIT, $unit);
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsiteId()
    {
        return $this->getData(self::WEBSITE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setWebsiteId($websiteId)
    {
        return $this->setData(self::WEBSITE_ID, $websiteId);
    }


    /**
     * {@inheritdoc}
     */
    public function getWebsite()
    {
        if (!$this->website) {
            $this->website = $this->websiteRepository->getById(
                $this->getWebsiteId()
            );
        }

        return $this->website;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function getFrequency()
    {
        return $this->getData(self::FREQUENCY);
    }

    /**
     * {@inheritdoc}
     */
    public function setFrequency($frequency)
    {
        return $this->setData(self::FREQUENCY, $frequency);
    }

    /**
     * {@inheritdoc}
     */
    public function getEngineCode()
    {
        return $this->getData(self::ENGINE_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setEngineCode($engine)
    {
        return $this->setData(self::ENGINE_CODE, $engine);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddresses()
    {
        return $this->getData(self::PROFILE_ADDRESSES);
    }

    /**
     * {@inheritdoc}
     */
    public function setAddresses($addresses)
    {
        return $this->setData(self::PROFILE_ADDRESSES, $addresses);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingAddress()
    {
        $result = null;
        $addresses = $this->getAddresses() ? $this->getAddresses() : [];
        foreach ($addresses as $address) {
            if ($address->getAddressType() === SubscriptionProfileAddressInterface::ADDRESS_TYPE_SHIPPING) {
                $result = $address;
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getBillingAddress()
    {
        $result = null;
        $addresses = $this->getAddresses() ? $this->getAddresses() : [];
        foreach ($addresses as $address) {
            if ($address->getAddressType() === SubscriptionProfileAddressInterface::ADDRESS_TYPE_BILLING) {
                $result = $address;
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartDate()
    {
        return $this->getData(self::START_DATE);
    }

    /**
     * @param string $startDate
     * @return $this
     */
    public function setStartDate($startDate)
    {
        return $this->setData(self::START_DATE, $startDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getTrialStartDate()
    {
        return $this->getData(self::TRIAL_START_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setTrialStartDate($trialStartDate)
    {
        return $this->setData(self::TRIAL_START_DATE, $trialStartDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getTerm()
    {
        return $this->getData(self::TERM);
    }

    /**
     * {@inheritdoc}
     */
    public function setTerm($term)
    {
        return $this->setData(self::TERM, $term);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalBillingCycles()
    {
        return $this->getData(self::TOTAL_BILLING_CYCLES);
    }

    /**
     * {@inheritdoc}
     */
    public function setTotalBillingCycles($totalBillingCycles)
    {
        return $this->setData(self::TOTAL_BILLING_CYCLES, $totalBillingCycles);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethod()
    {
        return $this->getData(self::SHIPPING_METHOD);
    }

    /**
     * {@inheritdoc}
     */
    public function setShippingMethod($shippingMethod)
    {
        return $this->setData(self::SHIPPING_METHOD, $shippingMethod);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingDescription()
    {
        return $this->getData(self::SHIPPING_DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function setShippingDescription($shippingDescription)
    {
        return $this->setData(self::SHIPPING_DESCRIPTION, $shippingDescription);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileCurrencyCode()
    {
        return $this->getData(self::PROFILE_CURRENCY_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setProfileCurrencyCode($profileCurrencyCode)
    {
        return $this->setData(self::PROFILE_CURRENCY_CODE, $profileCurrencyCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getTrialLength()
    {
        return $this->getData(self::TRIAL_LENGTH);
    }

    /**
     * {@inheritdoc}
     */
    public function setTrialLength($trialLength)
    {
        return $this->setData(self::TRIAL_LENGTH, $trialLength);
    }

    /**
     * {@inheritdoc}
     */
    public function getTrialLengthUnit()
    {
        return $this->getData(self::TRIAL_LENGTH_UNIT);
    }

    /**
     * {@inheritdoc}
     */
    public function setTrialLengthUnit($trialLengthUnit)
    {
        return $this->setData(self::TRIAL_LENGTH_UNIT, $trialLengthUnit);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsVirtual()
    {
        return $this->getData(self::IS_VIRTUAL);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsVirtual($isVirtual)
    {
        return $this->setData(self::IS_VIRTUAL, $isVirtual);
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts()
    {
        return $this->getData(self::PROFILE_PRODUCTS);
    }

    /**
     * {@inheritdoc}
     */
    public function setProducts($products)
    {
        return $this->setData(self::PROFILE_PRODUCTS, $products);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomer()
    {
        if (!$this->customer) {
            $this->customer = $this->customerRepository->getById(
                $this->getCustomerId()
            );
        }

        return $this->customer;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentToken()
    {
        return $this->encryptor->decrypt(
            $this->getData(self::TOKEN_HASH)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentToken($token)
    {
        return $this->setData(
            self::TOKEN_HASH,
            $this->encryptor->encrypt($token)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenHash()
    {
        return $this->getData(self::TOKEN_HASH);
    }

    /**
     * {@inheritdoc}
     */
    public function setTokenHash($tokenHash)
    {
        return $this->setData(self::TOKEN_HASH, $tokenHash);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo()
    {
        return $this->getData(self::PAYMENT_ADDITIONAL_INFO);
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentAdditionalInfo($info)
    {
        return $this->setData(self::PAYMENT_ADDITIONAL_INFO, $info);
    }


    /**
     * {@inheritdoc}
     */
    public function getDecodedPaymentAdditionalInfo()
    {
        return $this->jsonHelper->jsonDecode(
            $this->getData(self::PAYMENT_ADDITIONAL_INFO)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setEncodedPaymentAdditionalInfo($info)
    {
        return $this->setData(
            self::PAYMENT_ADDITIONAL_INFO,
            $this->jsonHelper->jsonEncode($info)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getGenerateQuotesState()
    {
        return $this->getData(self::GENERATE_QUOTES_STATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setGenerateQuotesState($state)
    {
        return $this->setData(self::GENERATE_QUOTES_STATE, $state);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($date)
    {
        return $this->setData(self::CREATED_AT, $date);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt($date)
    {
        return $this->setData(self::UPDATED_AT, $date);
    }

    /**
     * @inheritdoc
     */
    public function getNeedRecollect()
    {
        return $this->getData(self::NEED_RECOLLECT);
    }

    /**
     * @inheritdoc
     */
    public function setNeedRecollect($needRecollect)
    {
        return $this->setData(self::NEED_RECOLLECT, $needRecollect);
    }

    /**
     * @inheritdoc
     */
    public function getCancelBeforeNextCycle()
    {
        return $this->getData(self::CANCEL_BEFORE_NEXT_CYCLE);
    }

    /**
     * @inheritdoc
     */
    public function setCancelBeforeNextCycle($cancelBeforeNextCycle)
    {
        return $this->setData(self::CANCEL_BEFORE_NEXT_CYCLE, $cancelBeforeNextCycle);
    }

    /**
     * Get sum of all order's grand totals for current subscription profile.
     *
     * @return string
     */
    public function getCurrentValue()
    {
        return $this->getResource()->getCurrentValue($this);
    }

    /**
     * Get sum of all generated non-paid quote's grand totals for current subscription profile.
     *
     * @return string
     */
    public function getTotalValue()
    {
        return $this->getResource()->getTotalValue($this);
    }

    /**
     * Checks whether products need recollect
     *
     * @return bool
     */
    public function getProductNeedRecollect()
    {
        $result = false;
        /** @var ProductSubscriptionProfile $product */
        foreach ($this->getProducts() as $product) {
            if ($product->getNeedRecollect()) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getShippingBillingChangesMadeMessageForUpcomingOrders()
    {
        return __('Subscription changes made. Shipping Details and Grand Total may not reflect correct information until recalculation is complete.');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getShippingBillingChangesMadeMessageForSubscriptionDetails()
    {
        return __('Subscription changes made. Current, Annual and Total values may not reflect correct information until recalculation is complete.');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getProductChangesMadeMessageForProfit()
    {
        return __('Subscription products have changed. The graph may not show correct information until recalculation is complete.');
    }

    /**
     * Check if profile can be editable.
     * Depends on profile status.
     *
     * @return bool
     */
    public function canEditProfile()
    {
        $canEdit = false;
        $profileStatus = $this->getStatus();

        if (!in_array($profileStatus, [ProfileStatus::STATUS_CANCELED, ProfileStatus::STATUS_COMPLETE])) {
            $canEdit = true;
        }

        return $canEdit;
    }
}
