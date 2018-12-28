<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Model\AbstractExtensibleModel;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile as Resource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use TNW\Subscriptions\Api\ProductSubscriptionProfileAttributeRepositoryInterface;

/**
 * Product subscription profile model.
 */
class ProductSubscriptionProfile
    extends AbstractExtensibleModel
    implements ProductSubscriptionProfileInterface
{
    /**
     * Entity code.
     */
    const ENTITY = 'tnw_product_subscription_profile';

    /*
     * Default group code for custom attributes.
     */
    const DEFAULT_GROUP_CODE = 'additional-information';

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
     * ProductSubscriptionProfile constructor.
     * @param ModelContext $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductSubscriptionProfileAttributeRepositoryInterface $metadataService
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

        $this->productRepository = $productRepository;
        $this->metadataService = $metadataService;
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
        return $this->productRepository->getById($this->getMagentoProductId());
    }

    /**
     * @inheritdoc
     */
    public function getPrice()
    {
        $result = $this->getData(self::PRICE);
        if (!$this->getTnwSubscrUnlockPresetQty()) {
            $result *= $this->getQty();
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getUnitPrice()
    {
        $result = $this->getData(self::PRICE);
        if ($this->getTnwSubscrUnlockPresetQty()) {
            $result =  $this->getQty() ? round($result / $this->getQty()) : 0;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
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

        try {
            return \Zend_Json::decode($options);
        } catch (\Zend_Json_Exception $e) {
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function setCustomOptions($customOptions)
    {
        $this->setData(self::CUSTOM_OPTIONS, \Zend_Json::encode($customOptions));
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
