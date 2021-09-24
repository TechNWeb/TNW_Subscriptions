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
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

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
            'tnw_subscr_start_date' => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/start_date_type',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            'tnw_subscr_lock_product_price' => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/lock_product_price_status',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            'tnw_subscr_unlock_preset_qty' => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/unlock_preset_qty_status',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            'tnw_subscr_savings_calculation' => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/savings_calculation',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            'tnw_subscr_inf_subscriptions' => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/general/infinite_subscriptions',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            'tnw_subscr_offer_flat_discount' => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/discount/offer_flat_discount_status',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            'tnw_subscr_trial_status' => $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_status',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        ];


        if ($attributes['tnw_subscr_offer_flat_discount']) {
            $attributes['tnw_subscr_discount_amount'] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/discount/discount_amount',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes['tnw_subscr_discount_type'] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/discount/discount_type',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        if ($attributes['tnw_subscr_trial_status']) {
            $attributes['tnw_subscr_trial_price'] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_price',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes['tnw_subscr_trial_start_date'] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_start_date_type',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes['tnw_subscr_trial_length_unit'] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_length_unit',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes['tnw_subscr_trial_length'] = $this->scopeConfig->getValue(
                'tnw_subscriptions_product/trial/trial_length',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $attributes['tnw_subscr_trial_can_skip'] = 0;
        }

        return $attributes;
    }
}
