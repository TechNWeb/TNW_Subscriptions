<?php

/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Add default eav attribute value for mass update tnw_purchase_type
 *
 * Class UpdateExtensionAttributesForMassAction
 */
class UpdateExtensionAttributesForMassAction implements ObserverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * UpdateExtensionAttributesForMassAction constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function execute(Observer $observer)
    {
        $attributesData = $observer->getData('attributes_data');
        $productIds = $observer->getData('product_ids');
        $storeId = $observer->getData('store_id');
        foreach ($productIds as $productId) {
            $product = $this->productRepository->getById($productId, false, $storeId);
            $needleAttributes = $this->getAttributeCodeAndValue($storeId);
            foreach ($attributesData as $attributeKey => $attributeDatum) {
                if ($attributeKey == 'tnw_subscr_purchase_type' && $attributeDatum != 1) {
                    foreach ($needleAttributes as $key => $value) {
                        $attribute = $product->getCustomAttribute($key);
                        if ($attribute == null) {
                            $product->setCustomAttribute($key, $value);
                        }
                    }
                }
            }
            try {
                $this->productRepository->save($product);
            } catch (CouldNotSaveException $e) {
                $this->logger->error($e);

            }
        }
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getAttributeCodeAndValue($storeId)
    {
        $attributes = [
            Attribute::SUBSCRIPTION_START_DATE => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/start_date_type',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/lock_product_price_status',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/unlock_preset_qty_status',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            Attribute::SUBSCRIPTION_SAVINGS_CALCULATION => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/savings_calculation',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/infinite_subscriptions',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/discount/offer_flat_discount_status',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            Attribute::SUBSCRIPTION_TRIAL_STATUS => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_status',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        ];

        if ($attributes[Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT]) {
            $attributes[Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/discount/discount_amount',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes[Attribute::SUBSCRIPTION_DISCOUNT_TYPE] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/discount/discount_type',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        if ($attributes[Attribute::SUBSCRIPTION_TRIAL_STATUS]) {
            $attributes[Attribute::SUBSCRIPTION_TRIAL_PRICE] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_price',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes[Attribute::SUBSCRIPTION_TRIAL_START_DATE] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_start_date_type',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes[Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_length_unit',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes[Attribute::SUBSCRIPTION_TRIAL_LENGTH] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_length',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes[Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP] = 0;
        }

        return $attributes;
    }
}
