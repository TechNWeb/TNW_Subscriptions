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
use Psr\Log\LogLevel;

/**
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
     * UpdateExtensionAttributesForMassAction constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(ProductRepositoryInterface $productRepository, LoggerInterface $logger)
    {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
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
        $needleAttributes = $this->getAttributeCodeAndValue();
        foreach ($productIds as $productId) {
            $product = $this->productRepository->getById($productId, false, $observer->getData('store_id'));
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
                $this->logger->log($e, LogLevel::ERROR);

            }
        }
    }

    /**
     * @return array
     */
    public function getAttributeCodeAndValue()
    {
        return [
            'tnw_subscr_trial_status' => 0,
            'tnw_subscr_trial_length_unit' => 3,
            'tnw_subscr_trial_start_date' => 1,
            'tnw_subscr_start_date' => 1,
            'tnw_subscr_lock_product_price' => 0,
            'tnw_subscr_offer_flat_discount' => 0,
            'tnw_subscr_unlock_preset_qty' => 0,
            'tnw_subscr_savings_calculation' => 0,
            'tnw_subscr_inf_subscriptions' => 0,
            'tnw_subscr_hide_qty' => 0,
            'tnw_subscr_trial_can_skip' => 0
        ];
    }
}
