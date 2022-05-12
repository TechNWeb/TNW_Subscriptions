<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use TNW\Subscriptions\Api\ProductSubscriptionProfileAttributeRepositoryInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile as Resource;

/**
 * Product subscription profile model.
 */
class ProductSubscriptionProfile extends AbstractExtensibleModel implements ProductSubscriptionProfileInterface
{
    /**
     * Entity code.
     */
    const ENTITY = 'tnw_product_subscription_profile';

    /**
     * Default group code for custom attributes.
     */
    const DEFAULT_GROUP_CODE = 'additional-information';

    /**
     * @var QuoteDetailsInterfaceFactory
     */
    protected $quoteDetailsFactory;

    /**
     * @var TaxCalculationInterface
     */
    protected $taxCalculationService;

    /**
     * @var QuoteDetailsItemInterfaceFactory
     */
    protected $quoteDetailsItemFactory;

    /**
     * @var TaxClassKeyInterfaceFactory
     */
    protected $taxClassKeyFactory;

    /**
     * Repository for retrieving products.
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductSubscriptionProfileAttributeRepositoryInterface
     */
    private $metadataService;

    /**
     * @var GroupRepository;
     */
    private $groupRepository;

    /**
     * Attributes are that part of interface
     *
     * @var array
     */
    private $interfaceAttributes = [
        self::ID,
        self::PARENT_ID,
        self::SUBSCRIPTION_PROFILE_ID,
        self::MAGENTO_PRODUCT_ID,
        self::PRICE,
        self::INITIAL_FEE,
        self::QTY,
        self::PURCHASE_TYPE,
        self::TRIAL_STATUS,
        self::TRIAL_PRICE,
        self::LOCK_PRODUCT_PRICE_STATUS,
        self::OFFER_FLAT_DISCOUNT_STATUS,
        self::DISCOUNT_AMOUNT,
        self::DISCOUNT_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::NEED_RECOLLECT,
        self::NAME,
        self::SKU,
        self::TNW_SUBSCR_UNLOCK_PRESET_QTY,
        self::CUSTOM_OPTIONS,
    ];

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var GroupManagementInterface
     */
    private $groupManagement;

    /**
     * ProductSubscriptionProfile constructor.
     * @param ModelContext $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductSubscriptionProfileAttributeRepositoryInterface $metadataService
     * @param QuoteDetailsInterfaceFactory $quoteDetailsFactory
     * @param TaxCalculationInterface $taxCalculationService
     * @param QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory
     * @param TaxClassKeyInterfaceFactory $taxClassKeyFactory
     * @param GroupRepository $groupRepository
     * @param SerializerInterface $serializer
     * @param GroupManagementInterface $groupManagement
     * @param Resource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ModelContext $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        ProductRepositoryInterface $productRepository,
        ProductSubscriptionProfileAttributeRepositoryInterface $metadataService,
        QuoteDetailsInterfaceFactory $quoteDetailsFactory,
        TaxCalculationInterface $taxCalculationService,
        QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyFactory,
        GroupRepository $groupRepository,
        SerializerInterface $serializer,
        GroupManagementInterface $groupManagement,
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
        $this->groupManagement = $groupManagement;
        $this->taxClassKeyFactory = $taxClassKeyFactory;
        $this->quoteDetailsItemFactory = $quoteDetailsItemFactory;
        $this->quoteDetailsFactory = $quoteDetailsFactory;
        $this->taxCalculationService = $taxCalculationService;
        $this->productRepository = $productRepository;
        $this->metadataService = $metadataService;
        $this->groupRepository = $groupRepository;
        $this->serializer = $serializer;
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
    public function getSubscriptionProfileId()
    {
        return $this->getData(self::SUBSCRIPTION_PROFILE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setSubscriptionProfileId($subscriptionProfileId)
    {
        return $this->setData(self::SUBSCRIPTION_PROFILE_ID, $subscriptionProfileId);
    }

    /**
     * @inheritdoc
     */
    public function getMagentoProductId()
    {
        return $this->getData(self::MAGENTO_PRODUCT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setMagentoProductId($magentoProductId)
    {
        return $this->setData(self::MAGENTO_PRODUCT_ID, $magentoProductId);
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMagentoProduct()
    {
        try {
            $ptoduct = $this->productRepository->getById($this->getMagentoProductId());
        } catch (\Exception $e) {
            $ptoduct = null;
        }
        return $ptoduct;
    }

    /**
     * @inheritdoc
     */
    public function getPrice()
    {
        return (float) $this->getData(self::PRICE);
    }

    /**
     * @param $subscriptionProfile
     * @return int
     */
    public function getTaxAmount($subscriptionProfile)
    {
        try {
            $taxClassKey = $this->taxClassKeyFactory->create();
            $taxClassKey->setType(TaxClassKeyInterface::TYPE_ID);
            if ($this->getMagentoProduct()) {
                $taxClassKey->setValue($this->getMagentoProduct()->getTaxClassId());
            }

            if ($subscriptionProfile->getCustomer()) {
                $group = $this->groupRepository->getById($subscriptionProfile->getCustomer()->getGroupId());
                $customerId = $subscriptionProfile->getCustomerId();
            } else {
                $group = $this->groupManagement->getNotLoggedInGroup();
                $customerId = 0;
            }
            $customerTaxClassKey = $this->taxClassKeyFactory->create();
            $customerTaxClassKey->setType(TaxClassKeyInterface::TYPE_ID)
                ->setValue($group->getTaxClassId());

            $quoteDetails = $this->quoteDetailsFactory->create();
            $item = $this->quoteDetailsItemFactory->create();
            $item->setQuantity($this->getQty())
                ->setCode($this->getSku())
                ->setShortDescription(
                    $this->getMagentoProduct()
                        ? $this->getMagentoProduct()->getShortDescription()
                        : ''
                )
                ->setTaxClassKey($taxClassKey)
                ->setIsTaxIncluded(false)
                ->setType('product')
                ->setUnitPrice($this->getProfileUnitPrice(true));
            $quoteDetails->setShippingAddress($subscriptionProfile->getShippingAddress()->exportCustomerAddress())
                ->setBillingAddress($subscriptionProfile->getBillingAddress()->exportCustomerAddress())
                ->setCustomerTaxClassKey($customerTaxClassKey)
                ->setItems([$item])
                ->setCustomerId($customerId);
            $storeId = null;
            $taxDetails = $this->taxCalculationService->calculateTax($quoteDetails, $storeId, true);
        } catch (\Exception $e) {
            $taxDetails = [];
        }
        return isset($taxDetails['tax_amount']) ? $taxDetails['tax_amount'] : 0;
    }

    /**
     * @inheritdoc
     */
    public function getUnitPrice()
    {
        return $this->getProfileUnitPrice();
    }

    /**
     * Gets price.
     *
     * @param bool $divideByQty
     * @return string|null
     */
    public function getProfileUnitPrice($divideByQty = false)
    {
        $result = null;
        if (is_array($this->getData(self::PRICE))) {
            $result = $this->getData(self::PRICE)[0];
        } else {
            $result = $this->getData(self::PRICE);
        }

        if ($this->getTnwSubscrUnlockPresetQty() || $divideByQty) {
            $result = $this->getQty() ? round($result / $this->getQty(), 2) : 0;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function setPrice($price)
    {
        $result = null;
        if (is_array($price)) {
            $result = $this->setData(self::PRICE, $price[0]);
        } else {
            $result = $this->setData(self::PRICE, $price);
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getInitialFee()
    {
        return $this->getData(self::INITIAL_FEE);
    }

    /**
     * @inheritdoc
     */
    public function setInitialFee($initialFee)
    {
        return $this->setData(self::INITIAL_FEE, $initialFee);
    }

    /**
     * @inheritdoc
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * @inheritdoc
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * @inheritdoc
     */
    public function getPurchaseType()
    {
        return $this->getData(self::PURCHASE_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setPurchaseType($purchaseType)
    {
        return $this->setData(self::PURCHASE_TYPE, $purchaseType);
    }

    /**
     * @inheritdoc
     */
    public function getTrialStatus()
    {
        return $this->getData(self::TRIAL_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setTrialStatus($trialStatus)
    {
        return $this->setData(self::TRIAL_STATUS, $trialStatus);
    }

    /**
     * @inheritdoc
     */
    public function getTrialPrice()
    {
        return $this->getData(self::TRIAL_PRICE);
    }

    /**
     * @inheritdoc
     */
    public function setTrialPrice($trialPrice)
    {
        return $this->setData(self::TRIAL_PRICE, $trialPrice);
    }

    /**
     * @inheritdoc
     */
    public function getLockProductPriceStatus()
    {
        return $this->getData(self::LOCK_PRODUCT_PRICE_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setLockProductPriceStatus($lockProductPriceStatus)
    {
        return $this->setData(self::LOCK_PRODUCT_PRICE_STATUS, $lockProductPriceStatus);
    }

    /**
     * @inheritdoc
     */
    public function getOfferFlatDiscountStatus()
    {
        return $this->getData(self::OFFER_FLAT_DISCOUNT_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setOfferFlatDiscountStatus($offerFlatDiscountStatus)
    {
        return $this->setData(self::OFFER_FLAT_DISCOUNT_STATUS, $offerFlatDiscountStatus);
    }

    /**
     * @inheritdoc
     */
    public function getDiscountAmount()
    {
        return $this->getData(self::DISCOUNT_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setDiscountAmount($discountAmount)
    {
        return $this->setData(self::DISCOUNT_AMOUNT, $discountAmount);
    }

    /**
     * @inheritdoc
     */
    public function getDiscountType()
    {
        return $this->getData(self::DISCOUNT_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setDiscountType($discountType)
    {
        return $this->setData(self::DISCOUNT_TYPE, $discountType);
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
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName($productName)
    {
        $this->setData(self::NAME, $productName);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSku()
    {
        return $this->getData(self::SKU);
    }

    /**
     * @inheritdoc
     */
    public function setSku($productSku)
    {
        $this->setData(self::SKU, $productSku);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTnwSubscrUnlockPresetQty()
    {
        return $this->getData(self::TNW_SUBSCR_UNLOCK_PRESET_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setTnwSubscrUnlockPresetQty($subscrUnlockPresetQty)
    {
        $this->setData(self::TNW_SUBSCR_UNLOCK_PRESET_QTY, $subscrUnlockPresetQty);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setParentId($parentId)
    {
        $this->setData(self::PARENT_ID, $parentId);
        return $this;
    }

    /**
     * @inheritdoc
     * @throws
     */
    public function getCustomOptions()
    {
        $options = $this->getData(self::CUSTOM_OPTIONS);
        if (empty($options)) {
            return [];
        }

        $result = $this->serializer->unserialize($options);

        return is_array($result) ? $result : [];
    }

    /**
     * @inheritdoc
     */
    public function setCustomOptions($customOptions)
    {
        $this->setData(self::CUSTOM_OPTIONS, $this->serializer->serialize($customOptions));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
    {
        return $this->getData(self::CHILDREN) ?: [];
    }

    /**
     * @inheritdoc
     */
    public function setChildren(array $children)
    {
        $this->setData(self::CHILDREN, $children);
        return $this;
    }
}
