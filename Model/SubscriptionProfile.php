<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;
use TNW\Subscriptions\Api\SubscriptionProfileAttributeRepositoryInterface;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as Resource;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment\Collection as PaymentCollection;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment\CollectionFactory as PaymentCollectionFactory;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use Magento\Framework\Serialize\Serializer\Json;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileProfit;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder;
use TNW\Subscriptions\Model\SubscriptionProfile\ProfitCalculator;

/**
 * Subscription Profile model.
 * @method \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile getResource()
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
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'subscription_profile';

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
     * @var SubscriptionProfileAttributeRepositoryInterface
     */
    private $metadataService;

    /**
     * @var SubscriptionProfilePaymentInterface
     */
    private $payment;

    /**
     * @var PaymentCollectionFactory
     */
    private $paymentCollectionFactory;

    /**
     * @var UserContextInterface $userContext
     */
    private $userContext;

    /**
     * Attributes are that part of interface
     *
     * @var array
     */
    protected $interfaceAttributes = [
        self::WEBSITE_ID,
        self::STORE_ID,
        self::UNIT,
        self::CUSTOMER_ID,
        self::STATUS,
        self::FREQUENCY,
        self::ID,
        self::LABEL,
        self::BILLING_FREQUENCY_ID,
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
        self::CREATED_AT,
        self::UPDATED_AT,
        self::GENERATE_QUOTES_STATE,
        self::NEED_RECOLLECT,
        self::BILLING_CYCLES_TO_SKIP,
    ];

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var BillingFrequencyRepository
     */
    private $billingFrequencyRepository;

    /**
     * @var SubscriptionProfileProfit
     */
    private $profileProfit;

    /**
     * @var SubscriptionProfileOrder
     */
    private $subscriptionProfileOrder;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * SubscriptionProfile constructor.
     * @param ModelContext $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param SubscriptionProfileAttributeRepositoryInterface $metadataService
     * @param PaymentCollectionFactory $paymentCollectionFactory
     * @param UserContextInterface $userContext
     * @param Json $serializer
     * @param BillingFrequencyRepository $billingFrequencyRepository
     * @param SubscriptionProfileProfit $profileProfit
     * @param SubscriptionProfileOrder $subscriptionProfileOrder
     * @param \TNW\Subscriptions\Model\Config $config
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
        StoreRepositoryInterface $storeRepository,
        SubscriptionProfileAttributeRepositoryInterface $metadataService,
        PaymentCollectionFactory $paymentCollectionFactory,
        UserContextInterface $userContext,
        Json $serializer,
        BillingFrequencyRepository $billingFrequencyRepository,
        SubscriptionProfileProfit $profileProfit,
        SubscriptionProfileOrder $subscriptionProfileOrder,
        Config $config,
        Resource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );

        $this->customerRepository = $customerRepository;
        $this->websiteRepository = $websiteRepository;
        $this->metadataService = $metadataService;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->userContext = $userContext;
        $this->serializer = $serializer;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->profileProfit = $profileProfit;
        $this->subscriptionProfileOrder = $subscriptionProfileOrder;
        $this->storeRepository = $storeRepository;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Resource::class);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getBillingFrequencyId()
    {
        return $this->getData(self::BILLING_FREQUENCY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setBillingFrequencyId($billingFrequencyId)
    {
        return $this->setData(
            self::BILLING_FREQUENCY_ID,
            $billingFrequencyId
        );
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->config->getPrefix($this->getStore()) . $this->getId();
    }

    /**
     * @inheritdoc
     */
    public function getUnit()
    {
        return $this->getData(self::UNIT);
    }

    /**
     * @inheritdoc
     */
    public function setUnit($unit)
    {
        return $this->setData(self::UNIT, $unit);
    }

    /**
     * @inheritdoc
     */
    public function getWebsiteId()
    {
        return $this->getData(self::WEBSITE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setWebsiteId($websiteId)
    {
        return $this->setData(self::WEBSITE_ID, $websiteId);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     */
    public function getWebsite()
    {
        return $this->websiteRepository->getById($this->getWebsiteId());
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     */
    public function getStore()
    {
        if (!$this->getStoreId()) {
            return $this->getWebsite()->getDefaultStore();
        }
        return $this->storeRepository->getById($this->getStoreId());
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getFrequency()
    {
        return $this->getData(self::FREQUENCY);
    }

    /**
     * @inheritdoc
     */
    public function setFrequency($frequency)
    {
        return $this->setData(self::FREQUENCY, $frequency);
    }

    /**
     * @inheritdoc
     */
    public function getAddresses()
    {
        return $this->getData(self::PROFILE_ADDRESSES);
    }

    /**
     * @inheritdoc
     */
    public function setAddresses($addresses)
    {
        return $this->setData(self::PROFILE_ADDRESSES, $addresses);
    }

    /**
     * @inheritdoc
     */
    public function getShippingAddress()
    {
        $result = null;
        $addresses = $this->getAddresses() ?: [];
        foreach ($addresses as $address) {
            if ($address->getAddressType() === SubscriptionProfileAddressInterface::ADDRESS_TYPE_SHIPPING) {
                $result = $address;
                break;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getBillingAddress()
    {
        $result = null;
        $addresses = $this->getAddresses() ?: [];
        foreach ($addresses as $address) {
            if ($address->getAddressType() === SubscriptionProfileAddressInterface::ADDRESS_TYPE_BILLING) {
                $result = $address;
                break;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getTrialStartDate()
    {
        return $this->getData(self::TRIAL_START_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setTrialStartDate($trialStartDate)
    {
        return $this->setData(self::TRIAL_START_DATE, $trialStartDate);
    }

    /**
     * @inheritdoc
     */
    public function getTerm()
    {
        return $this->getData(self::TERM);
    }

    /**
     * @inheritdoc
     */
    public function setTerm($term)
    {
        return $this->setData(self::TERM, $term);
    }

    /**
     * @inheritdoc
     */
    public function getTotalBillingCycles()
    {
        return $this->getData(self::TOTAL_BILLING_CYCLES);
    }

    /**
     * @inheritdoc
     */
    public function setTotalBillingCycles($totalBillingCycles)
    {
        return $this->setData(self::TOTAL_BILLING_CYCLES, $totalBillingCycles);
    }

    /**
     * @inheritdoc
     */
    public function getShippingMethod()
    {
        return $this->getData(self::SHIPPING_METHOD);
    }

    /**
     * @inheritdoc
     */
    public function setShippingMethod($shippingMethod)
    {
        return $this->setData(self::SHIPPING_METHOD, $shippingMethod);
    }

    /**
     * @inheritdoc
     */
    public function getShippingDescription()
    {
        return $this->getData(self::SHIPPING_DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setShippingDescription($shippingDescription)
    {
        return $this->setData(self::SHIPPING_DESCRIPTION, $shippingDescription);
    }

    /**
     * @inheritdoc
     */
    public function getProfileCurrencyCode()
    {
        return $this->getData(self::PROFILE_CURRENCY_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setProfileCurrencyCode($profileCurrencyCode)
    {
        return $this->setData(self::PROFILE_CURRENCY_CODE, $profileCurrencyCode);
    }

    /**
     * @inheritdoc
     */
    public function getTrialLength()
    {
        return $this->getData(self::TRIAL_LENGTH);
    }

    /**
     * @inheritdoc
     */
    public function setTrialLength($trialLength)
    {
        return $this->setData(self::TRIAL_LENGTH, $trialLength);
    }

    /**
     * @inheritdoc
     */
    public function getTrialLengthUnit()
    {
        return $this->getData(self::TRIAL_LENGTH_UNIT);
    }

    /**
     * @inheritdoc
     */
    public function setTrialLengthUnit($trialLengthUnit)
    {
        return $this->setData(self::TRIAL_LENGTH_UNIT, $trialLengthUnit);
    }

    /**
     * @inheritdoc
     */
    public function getIsVirtual()
    {
        return $this->getData(self::IS_VIRTUAL);
    }

    /**
     * @inheritdoc
     */
    public function setIsVirtual($isVirtual)
    {
        return $this->setData(self::IS_VIRTUAL, $isVirtual);
    }

    /**
     * @inheritdoc
     */
    public function getProducts()
    {
        return $this->getData(self::PROFILE_PRODUCTS) ?: [];
    }

    /**
     * @inheritdoc
     */
    public function setProducts(array $products)
    {
        if ($this->getProducts()) {
            foreach ($this->getProducts() as $product) {
                foreach ($products as $newProduct) {
                    if ($newProduct->getMagentoProductId() == $product->getMagentoProductId()) {
                        $newProduct->setEntityId($product->getEntityId());
                    }
                }
            }
        }
        return $this->setData(self::PROFILE_PRODUCTS, $products);
    }

    /**
     * @inheritdoc
     */
    public function getVisibleProducts()
    {
        $items = [];
        foreach ($this->getProducts() as $item) {
            if ($item->getParentId()) {
                continue;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        try {
            $customer = $this->customerRepository->getById($this->getCustomerId());
        } catch (\Exception $e) {
            return null;
        }

        if ($this->userContext->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            $customer->setId(null);
        }
        return $customer;
    }

    /**
     * @inheritdoc
     */
    public function getGenerateQuotesState()
    {
        return $this->getData(self::GENERATE_QUOTES_STATE);
    }

    /**
     * @inheritdoc
     */
    public function setGenerateQuotesState($state)
    {
        return $this->setData(self::GENERATE_QUOTES_STATE, $state);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($date)
    {
        return $this->setData(self::CREATED_AT, $date);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getSkipBillingCycles()
    {
        return $this->getData(self::BILLING_CYCLES_TO_SKIP);
    }

    /**
     * @inheritdoc
     */
    public function setSkipBillingCycles($billingCyclesToSkip)
    {
        return $this->setData(self::BILLING_CYCLES_TO_SKIP, $billingCyclesToSkip);
    }

    /**
     * Get sum of all order's grand totals for current subscription profile.
     *
     * @return string
     */
    public function getCurrentValue()
    {
        return $this->getTotalProfit($this->getId(), ProfitCalculator::LIFETIME);
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
        return __('Subscription changes made. Shipping Details and Grand Total may not reflect correct '
            . 'information until recalculation is complete.');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getShippingBillingChangesMadeMessageForSubscriptionDetails()
    {
        return __('Subscription changes made. Current, Annual and Total values may not reflect correct '
            . 'information until recalculation is complete.');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getProductChangesMadeMessageForProfit()
    {
        return __('Subscription products have changed. The graph may not show correct information until '
            . 'recalculation is complete.');
    }

    /**
     * Check if profile can be editable.
     * Depends on profile status.
     *
     * @return bool
     */
    public function canEditProfile()
    {
        return !in_array(
            (int)$this->getStatus(),
            [
                ProfileStatus::STATUS_CANCELED,
                ProfileStatus::STATUS_COMPLETE,
            ],
            true
        );
    }

    /**
     * Retrieve subscription profile payment
     *
     * @return SubscriptionProfilePaymentInterface
     */
    public function getPayment()
    {
        if ($this->payment === null) {
            /** @var PaymentCollection $paymentCollection */
            $paymentCollection = $this->paymentCollectionFactory->create();
            $this->payment = $paymentCollection->setSubscriptionProfileFilter($this->getId())
                ->getFirstItem();
            if ($this->getId()) {
                $this->payment->setSubscriptionProfile($this);
            }
        }

        return $this->payment;
    }

    /**
     * Set payment to profile
     *
     * @param SubscriptionProfilePaymentInterface $payment
     * @return $this
     */
    public function setPayment(SubscriptionProfilePaymentInterface $payment)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Gets original start date.
     *
     * @return string|null
     */
    public function getOriginalStartDate()
    {
        return $this->getData(self::ORIGINAL_START_DATE);
    }

    /**
     * Sets original start date.
     *
     * @param string $originalStartDate
     * @return $this
     */
    public function setOriginalStartDate($originalStartDate)
    {
        return $this->setData(self::ORIGINAL_START_DATE, $originalStartDate);
    }

    /**
     * @inheritdoc
     */
    public function getCouponCode()
    {
        return $this->getData(self::COUPON_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setCouponCode($couponCode)
    {
        return $this->setData(self::COUPON_CODE, $couponCode);
    }

    /**
     * @inheritdoc
     */
    public function getStaticTotalBillingCycles()
    {
        return $this->getData(self::STATIC_TOTAL_BILLING_CYCLES);
    }

    /**
     * @inheritdoc
     */
    public function setStaticTotalBillingCycles($total)
    {
        return $this->setData(self::STATIC_TOTAL_BILLING_CYCLES, $total);
    }

    /**
     * Get is expiration date is now
     *
     * @param $subscriptionProfile
     * @param $finalDate
     * @return int|null
     */
    public function getCcEcpirationStatus($subscriptionProfile, $finalDate)
    {
        $result = null;
        $paymentInfo = $subscriptionProfile->getPayment()->getPaymentAdditionalInfo();
        if (isset($paymentInfo) && isset($finalDate)) {
            $getExpireDate = $this->serializer->unserialize($paymentInfo);
            if (isset($getExpireDate['cc_exp_month']) && isset($getExpireDate['cc_exp_year'])) {
                $ccExpMonth = (int) $getExpireDate['cc_exp_month'];
                $ccExpYear = (int) $getExpireDate['cc_exp_year'];
                $finalTimestamp = strtotime($finalDate);
                $finalDay = (int) date("d", $finalTimestamp) + 1;
                $expirationDate = strtotime($ccExpYear . '-' . $ccExpMonth . '-' . $finalDay);
                if ($finalTimestamp > $expirationDate) {
                    $result = 1;
                } else {
                    $result = 0;
                }
            }
        }
        return $result;
    }

    /**
     * @param SubscriptionProfile $profile
     * @param array $profileOrders
     * @return string|null
     */
    public function getFinalDateForInstallmentData($profile, $profileOrders)
    {
        $remainingBillingCycles = (int) $profile->getTotalBillingCycles() - 1;

        if ($profileOrders && count($profileOrders) == 1) {
            $remainingBillingCycles++;
        }

        return $this->getFinalDate($profile, $remainingBillingCycles, $profileOrders);
    }

    /**
     * @return string|null
     */
    public function getFinalDateForAccountInformation()
    {
        $remainingBillingCycles = (int) $this->getTotalBillingCycles();
        $profileOrders = $this->subscriptionProfileOrder->getProfileOrdersByProfileId(
            $this->getId()
        );

        return $this->getFinalDate($this, $remainingBillingCycles, $profileOrders);
    }

    /**
     * Get last day of recurring
     *
     * @param SubscriptionProfile $profile
     * @param int $remainingBillingCycles
     * @param array $profileOrders
     * @return string|null
     */
    private function getFinalDate($profile, $remainingBillingCycles, $profileOrders)
    {
        $startDate = $profile->getStartDate();
        $totalBillingCycles = $remainingBillingCycles + $this->getCountOrdersSinceStartDate($startDate, $profileOrders);

        if ($profile->getTerm() == 1 || $totalBillingCycles < 2) {
            return null;
        } elseif ($profile->getTerm() == 0) {
            if ($remainingBillingCycles == 1) {
                $order = $this->subscriptionProfileOrder->getLastProfileOrderByProfileId($profile->getId());
                if ($order && $order['magento_order_id'] === null) {
                    return $order['scheduled_at'];
                }
            } elseif ($remainingBillingCycles == 0 && $profileOrders) {
                return $this->getFinalOrderScheduledAt($profileOrders);
            }
        }

        return $this->calculateFinalDate($profile, $totalBillingCycles);
    }

    /**
     * @param array $profileOrders
     * @return string
     */
    private function getFinalOrderScheduledAt($profileOrders)
    {
        usort($profileOrders, function ($order1, $order2) {
            return strtotime($order2['scheduled_at']) - strtotime($order1['scheduled_at']);
        });
        return $profileOrders[0]['scheduled_at'];
    }

    /**
     * @param SubscriptionProfile $profile
     * @param int $totalBillingCycles
     * @return string|null
     */
    private function calculateFinalDate($profile, $totalBillingCycles)
    {
        try {
            $billingFrequency = $this->billingFrequencyRepository->getById(
                $profile->getBillingFrequencyId()
            );
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage());
            return null;
        }

        $startDate = $profile->getStartDate();
        $total = $billingFrequency->getFrequency() * ($totalBillingCycles - 1);
        switch ($billingFrequency->getUnit()) {
            case BillingFrequencyUnitType::DAYS:
                $billingCycles = "+" . $total . " days";
                $result = date("Y-m-d H:i:s", strtotime($billingCycles, strtotime($startDate)));
                break;
            case BillingFrequencyUnitType::MONTHS:
                $billingCycles = "+" . $total . " months";
                $result = date("Y-m-d H:i:s", strtotime($billingCycles, strtotime($startDate)));
                break;
            case BillingFrequencyUnitType::YEARS:
                $billingCycles = "+" . $total . " years";
                $result = date("Y-m-d H:i:s", strtotime($billingCycles, strtotime($startDate)));
                break;
            default:
                return null;
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getTotalProfit($id, $profitType)
    {
        return $this->profileProfit->getTotalProfitById($id, $profitType);
    }

    /**
     * @inheritdoc
     */
    public function setTotalProfit($profit)
    {
        return $this->profileProfit->setTotalProfit($profit);
    }

    /**
     * @return array
     */
    public function getFirstOrderData()
    {
        return $this->getResource()->getFirstOrderData($this);
    }

    /**
     * @param array $profileOrders
     * @return int
     */
    private function getCountOrdersSinceStartDate($startDate, $profileOrders)
    {
        $startDateTs = strtotime($startDate);

        return count(
            array_filter(
                $profileOrders,
                function ($order) use ($startDateTs) {
                    return $startDateTs <= strtotime($order['scheduled_at']);
                }
            )
        );
    }
}
