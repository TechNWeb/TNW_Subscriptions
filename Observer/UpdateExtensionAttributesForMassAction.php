<?php

/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use TNW\Subscriptions\Model\Product\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Add default eav attribute value for mass update tnw_purchase_type
 *
 * Class UpdateExtensionAttributesForMassAction
 */
class UpdateExtensionAttributesForMassAction implements ObserverInterface
{
    /**
     * @var Action
     */
    private $productAction;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateExtensionAttributesForMassAction constructor.
     * @param Action $productAction
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action $productAction,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->productAction = $productAction;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Update extension attribute for mass action.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $ids = [];
        $attributesData = $observer->getData('attributes_data');
        $productIds = $observer->getData('product_ids');
        $storeId = $observer->getData('store_id');
        $needleAttributes = $this->getAttributeCodeAndValue($storeId);

        foreach ($attributesData as $attributeKey => $attributeValue) {
            if ($attributeKey == Attribute::SUBSCRIPTION_PURCHASE_TYPE && $attributeValue != 1) {
                foreach ($productIds as $productId) {
                    $productAttribute = $this->productAction->getAttributeRawValue(
                        $productId,
                        array_keys($needleAttributes),
                        $storeId
                    );
                    if (empty($productAttribute)) {
                        $ids[] = $productId;
                    }
                }
                if ($ids) {
                    try {
                        $this->productAction->updateAttributes(array_unique($ids), $needleAttributes, $storeId);
                    } catch (\Exception $exception) {
                        $this->logger->log($exception->getMessage());
                    }
                }
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

        if (isset($attributes[Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT])) {
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

        if (isset($attributes[Attribute::SUBSCRIPTION_TRIAL_STATUS])) {
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
