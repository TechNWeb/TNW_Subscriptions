<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use TNW\Subscriptions\Model\ProductBillingFrequencyRepository;

include __DIR__ . '/product_billing_frequency.php';

/** @var ProductRepositoryInterface $productRepository */
$productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
/** @var ProductInterface $product */
$product = Bootstrap::getObjectManager()->create(ProductInterface::class);
$data = [
    'store_id' => 0,
    'type_id' => 'simple',
    'attribute_set_id' => 4,
    'tnw_subscr_purchase_type' => 3,
    'tnw_subscr_start_dat' => 1,
    'tnw_subscr_lock_poduct_price' => 0,
    'tnw_subscr_offer_flat_discount' => 0,
    'tnw_subscr_discount_amount' => 10,
    'tnw_subscr_discount_type' => 2,
    'tnw_subscr_trial_status' => 0,
    'tnw_subscr_trial_length' => 15,
    'tnw_subscr_trial_length_unit' => 3,
    'tnw_subscr_trial_price' => '',
    'tnw_subscr_trial_start_date' => 1,
    'stock_data' => [
        'min_qty_allowed_in_shopping_cart' =>
            [
                0 =>
                    [
                        'record_id' => '0',
                        'customer_group_id' => '32000',
                        'min_sale_qty' => '',
                    ],
            ],
        'min_qty' => '0',
        'max_sale_qty' => '10000',
        'notify_stock_qty' => '1',
        'qty_increments' => '1',
        'min_sale_qty' => '1',
        'use_config_manage_stock' => '1',
        'manage_stock' => '1',
        'use_config_min_qty' => '1',
        'use_config_max_sale_qty' => '1',
        'use_config_backorders' => '1',
        'backorders' => '0',
        'use_config_deferred_stock_update' => '1',
        'deferred_stock_update' => '1',
        'use_config_notify_stock_qty' => '1',
        'use_config_enable_qty_inc' => '1',
        'enable_qty_increments' => '0',
        'use_config_qty_increments' => '1',
        'use_config_min_sale_qty' => '1',
        'is_qty_decimal' => '0',
        'is_decimal_divided' => 0,
    ],
    'status' => 1,
    'name' => 's1',
    'price' => 10,
    'quantity_and_stock_status' => [
        'qty' => '100',
        'is_in_stock' => '1',
    ],
    'tnw_subscr_unlock_preset_qty' => 0,
    'website_ids' => [
        1 => '1',
    ],
    'tax_class_id' => 2,
    'sku' => 's1',
];

$product->addData($data);
$productRepository->save($product);
$product = $productRepository->get('s1');
$productBillingFrequency->setMagentoProductId($product->getId());
/** @var ProductBillingFrequencyRepository $productBillingFrequencyRepository */
$productBillingFrequencyRepository = Bootstrap::getObjectManager()->get(ProductBillingFrequencyRepository::class);
$productBillingFrequencyRepository->save($productBillingFrequency);

$product->setData('recurring_options', [$productBillingFrequency]);
$productRepository->save($product);
