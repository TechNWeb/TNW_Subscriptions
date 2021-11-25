<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductSubscriptionProfile;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as FrequencyRepository;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\ProductSubscriptionProfileFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;

/**
 * Class Manager - subscription products manager model
 */
class Manager
{
    /**
     * Factory for creating subscription profile product.
     *
     * @var ProductSubscriptionProfileFactory
     */
    private $profileProductFactory;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var MessageHistoryLogger
     */
    private $historyLogger;

    /**
     * Subscription profile product.
     *
     * @var ProductSubscriptionProfileInterface
     */
    private $profileProduct;

    /**
     * Mapper between subscription product and magento product attributes.
     *
     * @var array
     */
    private $productAttributesMap = [
        ProductSubscriptionProfile::MAGENTO_PRODUCT_ID => 'entity_id',
        ProductSubscriptionProfile::PURCHASE_TYPE => Attribute::SUBSCRIPTION_PURCHASE_TYPE,
        ProductSubscriptionProfile::TRIAL_STATUS => Attribute::SUBSCRIPTION_TRIAL_STATUS,
        ProductSubscriptionProfile::LOCK_PRODUCT_PRICE_STATUS => Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE,
        ProductSubscriptionProfile::OFFER_FLAT_DISCOUNT_STATUS => Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT,
        ProductSubscriptionProfile::DISCOUNT_AMOUNT => Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT,
        ProductSubscriptionProfile::DISCOUNT_TYPE => Attribute::SUBSCRIPTION_DISCOUNT_TYPE,
        ProductSubscriptionProfile::SKU => 'sku',
        ProductSubscriptionProfile::NAME => 'name',
        ProductSubscriptionProfile::TNW_SUBSCR_UNLOCK_PRESET_QTY => Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
    ];

    /**
     * @var Context
     */
    private $subscriptionContext;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var FrequencyRepository
     */
    private $frequencyRepository;

    /**
     * @var OrderRelationManager
     */
    private $orderRelationManager;

    /**
     * @var ProductTypeManagerResolver
     */
    private $productTypeResolver;

    /**
     * @param ProductSubscriptionProfileFactory $profileFactory
     * @param Registry $coreRegistry
     * @param MessageHistoryLogger $historyLogger
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param Context $subscriptionContext
     * @param SerializerInterface $serializer
     * @param FrequencyRepository $frequencyRepository
     * @param OrderRelationManager $orderRelationManager
     */
    public function __construct(
        ProductSubscriptionProfileFactory $profileFactory,
        Registry $coreRegistry,
        MessageHistoryLogger $historyLogger,
        ProductTypeManagerResolver $productTypeResolver,
        Context $subscriptionContext,
        SerializerInterface $serializer,
        FrequencyRepository $frequencyRepository,
        OrderRelationManager $orderRelationManager
    ) {
        $this->profileProductFactory = $profileFactory;
        $this->coreRegistry = $coreRegistry;
        $this->historyLogger = $historyLogger;
        $this->subscriptionContext = $subscriptionContext;
        $this->serializer = $serializer;
        $this->frequencyRepository = $frequencyRepository;
        $this->orderRelationManager = $orderRelationManager;
        $this->productTypeResolver = $productTypeResolver;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->profileProduct = null;
        return $this;
    }

    /**
     * Returns current/new profile product.
     *
     * @return ProductSubscriptionProfile
     */
    public function getProfileProduct()
    {
        if (!$this->profileProduct) {
            $this->profileProduct = $this->getEmptyProduct();
        }

        return $this->profileProduct;
    }

    /**
     * Sets profile product.
     *
     * @param ProductSubscriptionProfileInterface $profileProduct
     */
    public function setProfileProduct(
        ProductSubscriptionProfileInterface $profileProduct
    ) {
        $this->profileProduct = $profileProduct;
    }

    /**
     * Returns empty profile product.
     *
     * @return ProductSubscriptionProfile
     */
    public function getEmptyProduct()
    {
        return $this->profileProductFactory->create();
    }

    /**
     * Returns attributes mapper.
     *
     * @return array
     */
    private function getProductAttributesMap()
    {
        return $this->productAttributesMap;
    }

    /**
     * Returns list of main profile products created from quote items.
     *
     * @param \Magento\Quote\Model\Quote\Item[] $quoteItems
     * @return array
     */
    public function populateProductsData($quoteItems)
    {
        $profileProducts = [];
        /** @var Item $item */
        foreach ($quoteItems as $item) {
            $product = $this->reset()
                ->populateProductDataFromQuoteItem($item)
                ->getProfileProduct();
            $product->setQuoteItemId($item->getId());
            $profileProducts[] = $product;
        }

        return $profileProducts;
    }

    /**
     * Returns list of profile child products created from quote items.
     *
     * @param \Magento\Quote\Model\Quote\Item[] $quoteItems
     * @param ProductSubscriptionProfileInterface[] $products
     * @return array
     */
    public function populateChildProductsData($quoteItems, array $products)
    {
        $childProducts = [];
        /** @var Item $item */
        foreach ($quoteItems as $item) {
            switch ($item->getProductType()) {
                case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
                case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
                case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                    break;
                case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                    /** @var ProductSubscriptionProfileInterface $profileProduct */
                    $profileProduct = $this->getItemProfileProduct($item, $products);
                    if ($profileProduct) {
                        $configurableProducts = $this->getConfigurableProducts($item, $profileProduct);
                        if (!empty($configurableProducts)) {
                            $profileProduct->setChildren($configurableProducts);
                            $childProducts[] = $configurableProducts;
                        }
                    }
                    break;
                default:
                    throw new \InvalidArgumentException(__('Unsupported product type - %1', $item->getProductType()));
            }
        }
        $result = [];
        if ($childProducts) {
            foreach ($childProducts as $configurableProducts) {
                foreach ($configurableProducts as $configurableProduct) {
                    $result[] = $configurableProduct;
                }
            }
        }
        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Product $cartCandidate
     * @param \Magento\Framework\DataObject $requestData
     * @param DataObject|null $product
     * @param bool $zeroPrices
     * @return $this
     */
    public function populateProductDataFromCartCandidate(
        $cartCandidate,
        $requestData,
        DataObject $product = null,
        $zeroPrices = false
    ) {
        $product = $product ?: $cartCandidate;
        foreach ($this->getProductAttributesMap() as $profileProductField => $productField) {
            $this->getProfileProduct()->setData($profileProductField, $product->getData($productField));
        }

        $options = $cartCandidate->getTypeInstance()->getOrderOptions($cartCandidate);
        unset($options['info_buyRequest']['subscription_data']);
        $this->getProfileProduct()->setCustomOptions($options);

        $buyRequest = $requestData->getDataByPath(Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);
        if (!empty($buyRequest)) {
            $subscribedPrice = $this->getProductSubscribedPrice($zeroPrices, $buyRequest);
            $productPrice = $requestData->getData('custom_price');

            $this->getProfileProduct()->setInitialFee(0);
            //if subscription has trial period then current item price is trial price
            $this->getProfileProduct()->setTrialPrice(null);
            $this->getProfileProduct()->setPrice(!$zeroPrices ? $productPrice : 0);
            if ($buyRequest[Create::UNIQUE]['is_trial']) {
                $this->getProfileProduct()->setTrialPrice(!$zeroPrices ? $productPrice : 0);
                $this->getProfileProduct()->setPrice($subscribedPrice);
            }
        }
        $this->getProfileProduct()->setQty($cartCandidate->getQty());

        return $this;
    }

    /**
     * Sets to profile product data from quote item.
     *
     * @param Item $item
     * @param DataObject $product
     * @param bool $zeroPrices
     * @return $this
     */
    public function populateProductDataFromQuoteItem(
        Item $item,
        DataObject $product = null,
        $zeroPrices = false
    ) {
        $product = $product ?: $item->getProduct();
        foreach ($this->getProductAttributesMap() as $profileProductField => $productField) {
            $this->getProfileProduct()->setData($profileProductField, $product->getData($productField));
        }

        $options = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
        unset($options['info_buyRequest']['subscription_data']);
        $this->getProfileProduct()->setCustomOptions($options);

        $buyRequest = $item->getBuyRequest()->getDataByPath(Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);
        if (!empty($buyRequest)) {
            $initialFee = !$zeroPrices ? $this->getInitialFeeFromItem($item) : 0;
            $price = $this->getProductPrice($item, $zeroPrices, $buyRequest);
            $subscribedPrice = $this->getProductSubscribedPrice($zeroPrices, $buyRequest);
            $this->getProfileProduct()->setInitialFee($initialFee);
            //if subscription has trial period then current item price is trial price
            $this->getProfileProduct()->setTrialPrice(null);
            $this->getProfileProduct()->setPrice($price);
            if ($buyRequest[Create::UNIQUE]['is_trial']) {
                $this->getProfileProduct()->setTrialPrice($price);
                $this->getProfileProduct()->setPrice($subscribedPrice);
            }
        }
        $this->getProfileProduct()->setQty($item->getQty());

        return $this;
    }

    /**
     * Process products data from request.
     *
     * @param $data
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function processProfileProducts($data)
    {
        /** @var \TNW\Subscriptions\Model\SubscriptionProfile $profileModel */
        $profileModel = $this->coreRegistry->registry('tnw_subscription_profile');
        if ($profileModel) {
            $profileProducts = $profileModel->getProducts();
            $objectItemId = isset($data['objectItemId']) ? $data['objectItemId'] : false;
            if ($objectItemId) {
                /** @var \TNW\Subscriptions\Model\ProductSubscriptionProfile $product */
                foreach ($profileProducts as $product) {
                    // Restore original data
                    $product->setOrigData();
                    $productId = $product->getId();

                    if ($productId == $objectItemId) {
                        $productDataChanges = $product->hasDataChanges();
                        $product->setDataChanges(false);
                        $remove = isset($data['remove']);
                        $requestData = isset($data['item_' . $objectItemId]) ? $data['item_' . $objectItemId] : [];
                        if ($remove) {
                            $this->processRemove($product, $profileModel, $profileProducts);
                        } else {
                            $this->processData($profileModel, $product, $productId, $data, $requestData);
                        }
                        if ($product->hasDataChanges()) {
                            $product->setNeedRecollect('1');
                        }
                        $this->logFieldChanges($product, $profileModel);
                        $product->setDataChanges($productDataChanges || $product->hasDataChanges());
                    }
                }
            }
        }
    }

    /**
     * @param $profileModel
     * @param $product
     * @param $productId
     * @param $data
     * @param $requestData
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function processData(&$profileModel, &$product, $productId, $data, $requestData)
    {
        if (isset($data['admin_modification'])) {
            $requestData['admin_modification'] = $data['admin_modification'];
        }
        $priceFormat = $this->serializer->unserialize(
            $this->subscriptionContext->getPriceFormatData(
                $profileModel->getProfileCurrencyCode()
            )
        );
        if (!empty($requestData['price'])) {
            $product = $this->processPriceData($priceFormat, $requestData, $product);
        }
        if (!empty($requestData['qty'])) {
            $product->setQty(number_format($requestData['qty'], 4, '.', ''));
        }
        if (!empty($requestData['additional_attribute'])) {
            $product = $this->processAttributeChange($requestData, $product);
        }
        if (isset($data['item_' . $productId]['term'])) {
            $profileModel = $this->processTermChange(
                $data,
                $productId,
                $profileModel,
                $product
            );
        }
        $trialLength = $profileModel->getTrialLength();
        $originalStartDate = $trialLength
            ? date('Y-m-d', strtotime($profileModel->getTrialStartDate()))
            : date('Y-m-d', strtotime($profileModel->getOriginalStartDate()));
        if (isset($data['item_' . $productId]['start_on'])
            && (date('Y-m-d', strtotime($data['item_' . $productId]['start_on']))
                !== $originalStartDate)
        ) {
            $profileModel = $this->processStartOnDate(
                $data,
                $productId,
                $trialLength,
                $profileModel,
                $product
            );
        }
        if (isset($data['item_' . $productId]['billing_frequency'])) {
            $profileModel = $this->processBillingFrequencyData(
                $profileModel,
                $data,
                $productId,
                $originalStartDate,
                $product
            );
        }
        if ((bool) $product->getTnwSubscrUnlockPresetQty()) {
            $product = $this->processProductUnlockQty(
                $data,
                $productId,
                $product
            );
        }
        if (isset($data['item_' . $productId]['qty'])) {
            $product = $this->processProductQty(
                $product,
                $data,
                $productId,
                $profileModel
            );
        }
        return $this;
    }

    /**
     * @param $product
     * @param $profileModel
     * @param $profileProducts
     * @return $this
     */
    protected function processRemove($product, $profileModel, $profileProducts)
    {
        $product->delete();
        $this->historyLogger->log(
            __('Deleted product %1.', $product->getMagentoProduct()->getName()),
            $profileModel->getId()
        );

        foreach ($product->getChildren() as $children) {
            foreach ($profileProducts as $profileProduct) {
                if ($profileProduct->getId() != $children->getId()) {
                    continue;
                }
                $profileProduct->delete();
            }
        }
        return $this;
    }

    /**
     * @param $priceFormat
     * @param $requestData
     * @param $product
     * @return mixed
     */
    private function processPriceData($priceFormat, $requestData, $product)
    {
        $requestPrice = floatval(preg_replace(
            [
                '/[^\d' . $priceFormat['decimalSymbol'] . ']/',
                '/\\' . $priceFormat['decimalSymbol'] . '/'
            ],
            ['', '.'],
            $requestData['price']
        ));
        $product->setPrice($requestPrice);
        if (isset($requestData['admin_modification'])) {
            $product->setData('admin_modification', $requestData['admin_modification']);
        }
        return $product;
    }

    /**
     * @param $product
     * @param $profileModel
     * @return $this
     */
    private function logFieldChanges($product, $profileModel)
    {
        $fields = [
            ProductSubscriptionProfileInterface::PRICE => 'Price',
            ProductSubscriptionProfileInterface::QTY => 'Qty',
        ];
        foreach ($fields as $fieldName => $fieldLabel) {
            if ($product->dataHasChangedFor($fieldName)) {
                $message = __(
                    'Updated product <a href="{productUrl|%1}" '
                    . 'target="_blank">%2</a>. %3 changed from <b>%4</b> to <b>%5</b>.',
                    $product->getMagentoProduct()->getId(),
                    $product->getMagentoProduct()->getName(),
                    $fieldLabel,
                    $product->getOrigData($fieldName),
                    $product->getData($fieldName)
                );
                $this->historyLogger->log($message, $profileModel->getId());
            }
        }
        return $this;
    }

    /**
     * @param $requestData
     * @param $product
     * @return mixed
     */
    private function processAttributeChange($requestData, $product)
    {
        foreach ($requestData['additional_attribute'] as $attributeCode => $attributeValue) {
            if (is_array($attributeValue)) {
                $attributeValue = implode(',', $attributeValue);
            }

            $product->setCustomAttribute($attributeCode, $attributeValue);
        }
        return $product;
    }

    /**
     * @param $data
     * @param $productId
     * @param $profileModel
     * @param $product
     * @return string
     */
    private function processTermChange($data, $productId, $profileModel, $product)
    {
        $term = $data['item_' . $productId]['term'];
        $profileModel->setTerm($term);
        if (1 == $term) {
            $periodValue = 0;
        } else {
            $periodValue = isset($data['item_' . $productId]['period'])
                ? $data['item_' . $productId]['period']
                : 0;
            if (1 > $periodValue) {
                return (string) (__('Bill times must be greater than 0.'));
            }
        }
        $profileModel->setTotalBillingCycles($periodValue);

        $origBillingCycle = $profileModel->getOrigData(
            SubscriptionProfileInterface::TOTAL_BILLING_CYCLES
        );
        $billingCycle = $profileModel->getData(
            SubscriptionProfileInterface::TOTAL_BILLING_CYCLES
        );
        if ($profileModel->hasDataChanges(SubscriptionProfileInterface::TERM)
            && $origBillingCycle != $billingCycle
        ) {
            $message =  __(
                'Updated product <a href="{productUrl|%1}" target="_blank">%2</a>.'
                . '%3 changed from <b>%4</b> to <b>%5</b>.',
                $product->getMagentoProduct()->getId(),
                $product->getMagentoProduct()->getName(),
                'Term',
                $origBillingCycle == 0 ? __('Until canceled') : __(
                    'Bill %1 times',
                    $origBillingCycle
                ),
                $billingCycle == 0 ? __('Until canceled') : __(
                    'Bill %1 times',
                    $billingCycle
                )
            );

            $this->historyLogger->log($message, $profileModel->getId());
        }
        return $profileModel;
    }

    /**
     * @param $data
     * @param $productId
     * @param $trialLength
     * @param $profileModel
     * @param $product
     * @return mixed
     * @throws \Exception
     */
    private function processStartOnDate($data, $productId, $trialLength, $profileModel, $product)
    {
        $startOn = (new \DateTime($data['item_' . $productId]['start_on']))
            ->add(new \DateInterval($this->getCurrentTimeExpression()))
            ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        if ($trialLength) {
            $profileModel->setTrialStartDate($startOn);
            $intervalUnit = $profileModel->getTrialLengthUnit()
            == \TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType::MONTHS
                ? 'M'
                : 'D';
            $expression = 'P' . $trialLength . $intervalUnit;
            $startOn =  (new \DateTime($startOn))
                ->add(new \DateInterval($expression))
                ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        }
        $profileModel->setStartDate($startOn)
            ->setOriginalStartDate($startOn);

        if ($profileModel->hasDataChanges(SubscriptionProfileInterface::START_DATE)) {
            $message =  __(
                'Updated product <a href="{productUrl|%1}" target="_blank">%2</a>.'
                . '%3 changed from <b>%4</b> to <b>%5</b>.',
                $product->getMagentoProduct()->getId(),
                $product->getMagentoProduct()->getName(),
                'Start on',
                $profileModel->getOrigData(SubscriptionProfileInterface::START_DATE),
                $profileModel->getData(SubscriptionProfileInterface::START_DATE)
            );

            $this->historyLogger->log($message, $profileModel->getId());
        }
        return $profileModel;
    }

    /**
     * @param $data
     * @param $productId
     * @param $product
     * @return mixed
     */
    private function processProductUnlockQty($data, $productId, $product)
    {
        $billingFrequency = $data['item_' . $productId]['billing_frequency'];
        $recurringOptions = $product->getMagentoProduct()->getRecurringOptions();
        array_filter($recurringOptions, function ($v) use ($billingFrequency, &$product) {
            if ($v->getBillingFrequencyId() == $billingFrequency) {
                $product->setQty($v->getPresetQty());
                $product->setPrice($v->getPrice());
            }
        });
        return $product;
    }

    /**
     * @param $product
     * @param $data
     * @param $productId
     * @param $profileModel
     * @return mixed
     */
    private function processProductQty($product, $data, $productId, $profileModel)
    {
        $children = $product->getChildren();
        $child = !empty($children) && is_array($children) ? reset($children) : null;
        $productData = $child
            ? array_merge(
                $data['item_' . $productId],
                ['child_product' => $child->getMagentoProduct()]
            )
            : $data['item_' . $productId];
        if (isset($data['admin_modification'])) {
            $productData['admin_modification'] = $data['admin_modification'];
        }
        $price = $this->productTypeResolver->resolve($product->getMagentoProduct()->getTypeId())
            ->setProfile($profileModel)
            ->setOriginalProfileProduct($product)
            ->getSubscriptionPrice(
                $product->getMagentoProduct(),
                $productData
            );
        $product->setPrice($price);
        return $product;
    }

    /**
     * @param $profileModel
     * @param $data
     * @param $productId
     * @param $originalStartDate
     * @param $product
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processBillingFrequencyData($profileModel, $data, $productId, $originalStartDate, $product)
    {
        $frequencyId = $data['item_' . $productId]['billing_frequency'];
        if ($profileModel->getBillingFrequencyId() != $frequencyId) {
            if (!isset($startOn)
                && (strtotime(date('Y-m-d')) >= strtotime($originalStartDate))
            ) {
                $nextPaymentDate = $this->orderRelationManager
                    ->getNextProfileRelation($profileModel)->getScheduledAt();
                $startOn = $this->getNewStartDate($product, $nextPaymentDate);
                $startOn = (new \DateTime($startOn))
                    ->add(new \DateInterval($this->getCurrentTimeExpression()))
                    ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
                $profileModel->setStartDate($startOn);
            }
            $frequency = $this->frequencyRepository->getById($frequencyId);
            $profileModel->setBillingFrequencyId($frequencyId)
                ->setFrequency($frequency->getFrequency())
                ->setUnit($frequency->getUnit());

            if ($profileModel->hasDataChanges(
                SubscriptionProfileInterface::BILLING_FREQUENCY_ID
            )) {
                $message = __(
                    'Updated product <a href="{productUrl|%1}" target="_blank">%2</a>.'
                    . '%3 changed from <b>%4</b> to <b>%5</b>.',
                    $product->getMagentoProduct()->getId(),
                    $product->getMagentoProduct()->getName(),
                    'Billing Frequency',
                    $this->frequencyRepository->getById(
                        $profileModel->getOrigData(
                            SubscriptionProfileInterface::BILLING_FREQUENCY_ID
                        )
                    )->getLabel(),
                    $this->frequencyRepository->getById(
                        $profileModel->getData(
                            SubscriptionProfileInterface::BILLING_FREQUENCY_ID
                        )
                    )->getLabel()
                );

                $this->historyLogger->log($message, $profileModel->getId());
            }
        }
        return $profileModel;
    }

    /**
     * @param Item $item
     * @param ProductSubscriptionProfileInterface $subscriptionProduct
     * @return array
     */
    private function getConfigurableProducts(
        Item $item,
        ProductSubscriptionProfileInterface $subscriptionProduct
    ) {
        $products = [];

        $productObject = new DataObject($item->getProduct()->getData());
        $productObject
            ->setData('name', $item->getName())
            ->setData('sku', $item->getSku());

        foreach ($item->getChildren() as $child) {
            $productObject->setData('entity_id', $child->getProduct()->getId());
            $product = $this->reset()
                ->populateProductDataFromQuoteItem($child, $productObject, true)
                ->getProfileProduct();
            $products[] = $product;
        }

        return $products;
    }

    /**
     * Returns initial fee from item.
     *
     * @param Item $item
     * @return int
     */
    private function getInitialFeeFromItem(Item $item)
    {
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
        }

        return !empty($initialFee) ? $initialFee : 0;
    }

    /**
     * Returns subscription product for quote item.
     *
     * @param Item $item
     * @param ProductSubscriptionProfileInterface[] $products
     * @return bool|ProductSubscriptionProfileInterface
     */
    private function getItemProfileProduct($item, $products)
    {
        $itemId = $item->getId();
        $result = array_filter(
            $products,
            function (ProductSubscriptionProfileInterface $product) use ($itemId) {
                return ($product->getQuoteItemId() == $itemId);
            }
        );

        return $result ? reset($result) : false;
    }

    /**
     * Returns product subscription full price.
     * Full means with trial and initial fee options.
     *
     * @param Item $item
     * @param bool $zeroPrices
     * @param array $buyRequest
     * @return float|int|null
     */
    protected function getProductPrice(Item $item, $zeroPrices, array $buyRequest)
    {
        if (isset($buyRequest[Create::UNIQUE]['use_preset_qty']) && $buyRequest[Create::UNIQUE]['use_preset_qty']) {
            $price = $item->getRowTotal();
        } else {
            $price = $item->getPrice();
        }
        return !$zeroPrices ? $price : 0;
    }

    /**
     * Returns product subscription price.
     *
     * @param bool $zeroPrices
     * @param array $buyRequest
     * @return int|string|float
     */
    protected function getProductSubscribedPrice($zeroPrices, array $buyRequest)
    {
        $presetQtyPrice = !empty($buyRequest[Create::NON_UNIQUE]['preset_qty_price'])
            ? $buyRequest[Create::NON_UNIQUE]['preset_qty_price']
            : 0;
        $productPrice = !empty($buyRequest[Create::NON_UNIQUE]['price'])
            ? $buyRequest[Create::NON_UNIQUE]['price']
            : 0;
        $price = !empty($buyRequest[Create::UNIQUE]['use_preset_qty'])
            ? $presetQtyPrice
            : $productPrice;

        return !$zeroPrices ? $price : 0;
    }

    /**
     * Calculates new start date for subscription.
     *
     * @param ProductSubscriptionProfile $product
     * @param string $nextDate
     * @return string
     */
    protected function getNewStartDate($product, $nextDate = null)
    {
        $startDateType = $product->getData(Attribute::SUBSCRIPTION_START_DATE);
        $nextDate = date_create($nextDate)->format('Y-m-d');
        switch ($startDateType) {
            case StartDateType::LAST_DAY_OF_THE_CURRENT_MONTH:
                $result = date_create()->format('Y-m-t');
                break;
            case StartDateType::FIRST_DAY_OF_THE_MONTH:
                $result = date_create()->format('Y-m-01');
                if (strtotime($result) < strtotime($nextDate)) {
                    $result = new \DateTime();
                    $result = $result->format('Y-') . ($result->format('m') + 1) . '-' . '01';
                }
                break;
            case StartDateType::ON_15TH_OF_THE_MONTH:
                $result = date_create()->format('Y-m-15');
                if (strtotime($result) < strtotime($nextDate)) {
                    $result = new \DateTime();
                    $result = $result->format('Y-') . ($result->format('m') + 1) . '-' . '15';
                }
                break;
            default:
                $result = $nextDate;
                break;
        }

        return $result;
    }

    /**
     * Get current time expression.
     *
     * @return string
     * @throws \Exception
     */
    protected function getCurrentTimeExpression()
    {
        $date = new \DateTime();
        return
            'PT' . $date->format('H') . 'H' . $date->format('i') . 'M' . $date->format('s') . 'S';
    }
}
