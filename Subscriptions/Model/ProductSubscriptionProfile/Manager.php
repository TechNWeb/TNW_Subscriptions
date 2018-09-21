<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\ProductSubscriptionProfileFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;

/**
 * Class Manager
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
     * @param ProductSubscriptionProfileFactory $profileFactory
     * @param Registry $coreRegistry
     * @param MessageHistoryLogger $historyLogger
     */
    public function __construct(
        ProductSubscriptionProfileFactory $profileFactory,
        Registry $coreRegistry,
        MessageHistoryLogger $historyLogger
    ) {
        $this->profileProductFactory = $profileFactory;
        $this->coreRegistry = $coreRegistry;
        $this->historyLogger = $historyLogger;
    }

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
     * @param Quote $quote
     * @return array
     */
    public function populateProductsData(Quote $quote)
    {
        $items = $quote->getAllVisibleItems();
        $profileProducts = [];
        /** @var Item $item */
        foreach ($items as $item) {
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
     * @param Quote $quote
     * @param ProductSubscriptionProfileInterface[] $products
     * @return array
     */
    public function populateChildProductsData(Quote $quote, array $products)
    {
        $childProducts = [];
        $items = $quote->getAllVisibleItems();
        /** @var Item $item */
        foreach ($items as $item) {
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
                            $childProducts = array_merge($childProducts, $configurableProducts);
                        }
                    }
                    break;
                default:
                    throw new \InvalidArgumentException(__('Unsupported product type - %1', $item->getProductType()));
            }
        }

        return $childProducts;
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

                    if ($product->getId() == $objectItemId) {
                        $productDataChanges = $product->hasDataChanges();
                        $product->setDataChanges(false);
                        $remove = isset($data['remove']);
                        $requestData = isset($data['item_' . $objectItemId]) ? $data['item_' . $objectItemId] : [];
                        if ($remove) {
                            $product->delete();
                            $this->historyLogger->log(__('Deleted product %1.',
                                $product->getMagentoProduct()->getName()), $profileModel->getId());
                        } else {
                            if (!empty($requestData['price'])) {
                                $product->setPrice(number_format($requestData['price'], 4));
                            }
                            if (!empty($requestData['qty'])) {
                                $product->setQty(number_format($requestData['qty'], 4));
                            }
                            if (!empty($requestData['additional_attribute'])) {
                                foreach ($requestData['additional_attribute'] as $attributeCode => $attributeValue) {
                                    if (is_array($attributeValue)) {
                                        $attributeValue = implode(',', $attributeValue);
                                    }

                                    $product->setCustomAttribute($attributeCode, $attributeValue);
                                }
                            }
                        }
                        if ($product->hasDataChanges()) {
                            $product->setNeedRecollect('1');
                        }

                        $fields = [
                            ProductSubscriptionProfileInterface::PRICE => 'Price',
                            ProductSubscriptionProfileInterface::QTY => 'Qty',
                        ];
                        foreach ($fields as $fieldName => $fieldLabel) {
                            if ($product->dataHasChangedFor($fieldName)) {
                                $message = __('Updated product <a href="{productUrl|%1}" target="_blank">%2</a>. %3 changed from <b>%4</b> to <b>%5</b>.',
                                    $product->getMagentoProduct()->getId(),
                                    $product->getMagentoProduct()->getName(),
                                    $fieldLabel,
                                    $product->getOrigData($fieldName),
                                    $product->getData($fieldName)
                                );
                                $this->historyLogger->log($message, $profileModel->getId());
                            }
                        }

                        $product->setDataChanges($productDataChanges || $product->hasDataChanges());
                    }
                }
            }
        }
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
        $confOptions = $item->getBuyRequest()->getDataByPath('super_attribute');
        $subscriptionProduct->setCustomOptions(\Zend_Json::encode($confOptions));

        $productObject = new DataObject($item->getProduct()->getData());
        $productObject
            ->setData('name', $item->getName())
            ->setData('sku', $item->getSku());

        foreach ($item->getChildren() as $child) {
            $productObject->setData('entity_id', $child->getProduct()->getId());
            $product = $this->reset()
                ->populateProductDataFromQuoteItem($child, $productObject, true)
                ->getProfileProduct();
            $product->setCustomOptions(\Zend_Json::encode($confOptions));
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
        $price = isset($buyRequest[Create::UNIQUE]['use_preset_qty'])
            ? $item->getRowTotal()
            : $item->getPrice();

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
}
