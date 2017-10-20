<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
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
     * Repository for retrieving products.
     *
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * Subscription profile product.
     *
     * @var ProductSubscriptionProfileInterface
     */
    private $profileProduct;

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
     * Manager constructor.
     * @param ProductSubscriptionProfileFactory $profileFactory
     * @param ProductRepository $productRepository
     * @param Registry $coreRegistry
     * @param MessageHistoryLogger $historyLogger
     */
    public function __construct(
        ProductSubscriptionProfileFactory $profileFactory,
        ProductRepository $productRepository,
        Registry $coreRegistry,
        MessageHistoryLogger $historyLogger
    ) {
        $this->profileProductFactory = $profileFactory;
        $this->productRepository = $productRepository;
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
     * @param ProductSubscriptionProfile $profileProduct
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
     * Sets to profile product data from quote item.
     *
     * @param Item $item
     * @return $this
     */
    public function populateProductDataFromQuoteItem(Item $item)
    {
        $product = $this->productRepository->getById(
            $item->getProduct()->getId()
        );

        foreach ($this->getProductAttributesMap() as $profileProductField => $productField) {
            $this->getProfileProduct()->setData(
                $profileProductField,
                $product->getData($productField)
            );
        }

        $buyRequest = $item->getBuyRequest()->getDataByPath(
            Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
        );

        if (!empty($buyRequest)) {
            $this->getProfileProduct()->setInitialFee(
                $this->getProductInitialFee(
                    $product,
                    $buyRequest[Create::UNIQUE]['billing_frequency']
                )
            );
            $this->getProfileProduct()->setTrialPrice(null);
            $this->getProfileProduct()->setPrice($item->getPrice());

            if ($buyRequest[Create::UNIQUE]['is_trial']) {
                $this->getProfileProduct()->setTrialPrice($item->getPrice());
                $this->getProfileProduct()->setPrice($buyRequest[Create::NON_UNIQUE]['price']);
            }
        }

        $this->getProfileProduct()->setQty($item->getQty());

        return $this;
    }

    /**
     * @param Product $product
     * @param int $frequencyId
     * @return null
     */
    private function getProductInitialFee($product, $frequencyId)
    {
        $initialFee = null;

        if ($product->getData('recurring_options')){
            /** @var ProductBillingFrequencyInterface $option */
            foreach ($product->getData('recurring_options') as $option) {
                if ($option->getBillingFrequencyId() === $frequencyId){
                    $initialFee = $option->getInitialFee();
                    break;
                }
            }
        }

        return $initialFee;
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
                        $requestData = isset($data['item_' . $objectItemId]) ? $data['item_' . $objectItemId] : false;
                        if ($remove) {
                            $product->delete();
                            $this->historyLogger->log(__('Deleted product %1.', $product->getMagentoProduct()->getName()), $profileModel->getId());
                        } else {
                            $product->setPrice(number_format($requestData['price'], 4));
                            $product->setQty(number_format($requestData['qty'], 4));
                        }
                        if ($product->hasDataChanges()) {
                            $product->setNeedRecollect('1');
                        }

                        if ($product->dataHasChangedFor(ProductSubscriptionProfileInterface::PRICE)) {
                            $message = __('Updated product <a href="%1" target="_blank">%2</a>. Price changed from <b>%3</b> to <b>%4</b>.',
                                $product->getMagentoProduct()->getProductUrl(),
                                $product->getMagentoProduct()->getName(),
                                $product->getOrigData(ProductSubscriptionProfileInterface::PRICE),
                                $product->getData(ProductSubscriptionProfileInterface::PRICE));

                            $this->historyLogger->log($message, $profileModel->getId());
                        }

                        if ($product->dataHasChangedFor(ProductSubscriptionProfileInterface::QTY)) {
                            $message = __('Updated product <a href="%1" target="_blank">%2</a>. Qty changed from <b>%3</b> to <b>%4</b>.',
                                $product->getMagentoProduct()->getProductUrl(),
                                $product->getMagentoProduct()->getName(),
                                $product->getOrigData(ProductSubscriptionProfileInterface::QTY),
                                $product->getData(ProductSubscriptionProfileInterface::QTY));

                            $this->historyLogger->log($message, $profileModel->getId());
                        }

                        $product->setDataChanges($productDataChanges || $product->hasDataChanges());
                    }
                }
            }
        }
    }
}