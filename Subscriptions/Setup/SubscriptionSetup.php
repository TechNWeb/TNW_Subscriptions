<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Eav\Setup\EavSetup;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Setup for subscription.
 */
class SubscriptionSetup extends EavSetup
{
    /**
     * Default entity and attributes.
     *
     * @return array
     */
    public function getDefaultEntities()
    {
        return [
            \TNW\Subscriptions\Model\SubscriptionProfile::ENTITY => [
                'entity_model' => \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile::class,
                'table' => SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                'attributes' => [
                    'customer_id' => [
                        'type' => 'static',
                        'label' => 'Customer Id',
                        'required' => false,
                        'sort_order' => 10,
                        'visible' => true,
                    ],
                    'billing_frequency_id' => [
                        'type' => 'static',
                        'label' => 'Billing Frequency Id',
                        'required' => false,
                        'sort_order' => 20,
                        'visible' => true,
                    ],
                    'label' => [
                        'type' => 'static',
                        'label' => 'Label',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    'unit' => [
                        'type' => 'static',
                        'label' => 'Unit',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    'website_id' => [
                        'type' => 'static',
                        'label' => 'Website Id',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    'status' => [
                        'type' => 'static',
                        'label' => 'Status',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    'frequency' => [
                        'type' => 'static',
                        'label' => 'Frequency',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    'engine_code' => [
                        'type' => 'static',
                        'label' => 'Engine Code',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    'shipping_address_id' => [
                        'type' => 'static',
                        'label' => 'Shipping Address Id',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    'billing_address_id' => [
                        'type' => 'static',
                        'label' => 'Billing Address Id',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    'created_at' => [
                        'type' => 'static',
                        'label' => 'Created At',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    'updated_at' => [
                        'type' => 'static',
                        'label' => 'Updated At',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                ],
            ],
            \TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY => [
                'entity_model' => \TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile::class,
                'table' => \TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY_TABLE,
                'attributes' => [
                    'price' => [
                        'type' => 'static',
                        'label' => 'Price',
                        'input' => 'price',
                        'required' => true,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 10,
                    ],
                    'initial_fee' => [
                        'type' => 'static',
                        'label' => 'Initial Fee',
                        'input' => 'price',
                        'required' => true,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 20,
                    ],
                    'qty' => [
                        'type' => 'static',
                        'label' => 'Qty',
                        'input' => 'text',
                        'required' => true,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 30,
                    ],
                    'purchase_type' => [
                        'type' => 'static',
                        'label' => 'Purchase Type',
                        'input' => 'select',
                        'source' => \TNW\Subscriptions\Model\Config\Source\PurchaseType::class,
                        'required' => true,
                        'sort_order' => 40,
                    ],
                    'trial_status' => [
                        'type' => 'static',
                        'label' => 'Trial Status',
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => false,
                        'sort_order' => 50,
                    ],
                    'trial_length' => [
                        'type' => 'static',
                        'label' => 'Trial Length',
                        'input' => 'text',
                        'required' => false,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 60,
                    ],
                    'trial_length_unit' => [
                        'type' => 'static',
                        'label' => 'Trial Length Unit',
                        'input' => 'select',
                        'required' => false,
                        'source' => \TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType::class,
                        'sort_order' => 70,
                    ],
                    'trial_price' => [
                        'type' => 'static',
                        'label' => 'Trial Price',
                        'input' => 'price',
                        'required' => false,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 80,
                    ],
                    'trial_start_date' => [
                        'type' => 'static',
                        'label' => 'Trial Start Date',
                        'input' => 'select',
                        'required' => false,
                        'source' => \TNW\Subscriptions\Model\Config\Source\StartDateType::class,
                        'sort_order' => 90,
                    ],
                    'start_date' => [
                        'type' => 'static',
                        'label' => 'Start Date',
                        'input' => 'select',
                        'required' => false,
                        'source' => \TNW\Subscriptions\Model\Config\Source\StartDateType::class,
                        'sort_order' => 100,
                    ],
                    'lock_product_price_status' => [
                        'type' => 'static',
                        'label' => 'Lock product price',
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => true,
                        'sort_order' => 110,
                    ],
                    'offer_flat_discount_status' => [
                        'type' => 'static',
                        'label' => 'Offer flat discount',
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => true,
                        'sort_order' => 110,
                    ],
                    'discount_amount' => [
                        'type' => 'static',
                        'label' => 'Discount amount',
                        'input' => 'price',
                        'required' => false,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 120,
                    ],
                    'discount_type' => [
                        'type' => 'static',
                        'label' => 'Discount type',
                        'input' => 'select',
                        'required' => false,
                        'source' => \TNW\Subscriptions\Model\Config\Source\DiscountType::class,
                        'sort_order' => 130,
                    ],
                ],
            ],
        ];
    }
}
