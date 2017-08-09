<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as Resource;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Json\Helper\Data;

/**
 * Subscription Profile model.
 */
class SubscriptionProfile extends AbstractModel implements SubscriptionProfileInterface
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
     * Repository for retrieving customers.
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Profile customer.
     *
     * @var CustomerInterface
     */
    private $customer;

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
     * SubscriptionProfile constructor.
     * @param ModelContext $context
     * @param Registry $registry
     * @param CustomerRepositoryInterface $customerRepository
     * @param EncryptorInterface $encryptor
     * @param Data $jsonHelper
     * @param Resource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ModelContext $context,
        Registry $registry,
        CustomerRepositoryInterface $customerRepository,
        EncryptorInterface $encryptor,
        Data $jsonHelper,
        Resource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->encryptor = $encryptor;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
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
        return sprintf('#S-%s', $this->getId());
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
        foreach ($this->getAddresses() as $address) {
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
        foreach ($this->getAddresses() as $address) {
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
    public function setPaymentToken($tokenHash)
    {
        return $this->setData(
            self::TOKEN_HASH,
            $this->encryptor->encrypt($tokenHash)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo()
    {
        return $this->jsonHelper->jsonDecode(
            $this->getData(self::PAYMENT_ADDITIONAL_INFO)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentAdditionalInfo($info)
    {
        return $this->setData(
            self::PAYMENT_ADDITIONAL_INFO,
            $this->jsonHelper->jsonEncode($info)
        );
    }
}
