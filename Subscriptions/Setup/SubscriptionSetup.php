<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Eav\Setup\EavSetup;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
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
                    SubscriptionProfile::CUSTOMER_ID => [
                        'type' => 'static',
                        'label' => 'Customer Id',
                        'required' => false,
                        'sort_order' => 10,
                        'visible' => true,
                    ],
                    SubscriptionProfile::BILLING_FREQUENCY_ID => [
                        'type' => 'static',
                        'label' => 'Billing Frequency Id',
                        'required' => false,
                        'sort_order' => 20,
                        'visible' => true,
                    ],
                    SubscriptionProfile::UNIT => [
                        'type' => 'static',
                        'label' => 'Unit',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                    ],
                    SubscriptionProfile::WEBSITE_ID => [
                        'type' => 'static',
                        'label' => 'Website Id',
                        'required' => false,
                        'sort_order' => 40,
                        'visible' => true,
                    ],
                    SubscriptionProfile::STATUS => [
                        'type' => 'static',
                        'label' => 'Status',
                        'required' => false,
                        'sort_order' => 50,
                        'visible' => true,
                    ],
                    SubscriptionProfile::FREQUENCY => [
                        'type' => 'static',
                        'label' => 'Frequency',
                        'required' => false,
                        'sort_order' => 60,
                        'visible' => true,
                    ],
                    SubscriptionProfile::ENGINE_CODE => [
                        'type' => 'static',
                        'label' => 'Engine Code',
                        'required' => false,
                        'sort_order' => 70,
                        'visible' => true,
                    ],
                    SubscriptionProfile::START_DATE => [
                        'type' => 'static',
                        'label' => 'Start Date',
                        'input' => 'date',
                        'required' => false,
                        'visible' => true,
                        'sort_order' => 80
                    ],
                    SubscriptionProfile::TRIAL_START_DATE => [
                        'type' => 'static',
                        'label' => 'Trial Start Date',
                        'input' => 'date',
                        'required' => false,
                        'visible' => true,
                        'sort_order' => 90,
                    ],
                    SubscriptionProfile::TRIAL_LENGTH => [
                        'type' => 'static',
                        'label' => 'Trial Length',
                        'input' => 'text',
                        'required' => false,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 100,
                    ],
                    SubscriptionProfile::TRIAL_LENGTH_UNIT => [
                        'type' => 'static',
                        'label' => 'Trial Length Unit',
                        'input' => 'select',
                        'required' => false,
                        'source' => \TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType::class,
                        'sort_order' => 110,
                    ],
                    SubscriptionProfile::TERM => [
                        'type' => 'static',
                        'label' => 'Term',
                        'input' => 'text',
                        'required' => false,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 120,
                    ],
                    SubscriptionProfile::TOTAL_BILLING_CYCLES => [
                        'type' => 'static',
                        'label' => 'Total billing cycles',
                        'input' => 'text',
                        'required' => false,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 130,
                    ],
                    SubscriptionProfile::SHIPPING_METHOD => [
                        'type' => 'static',
                        'label' => 'Shipping Method',
                        'input' => 'text',
                        'required' => true,
                        'frontend_class' => 'validate-length maximum-length-40',
                        'sort_order' => 140,
                    ],
                    SubscriptionProfile::SHIPPING_DESCRIPTION => [
                        'type' => 'static',
                        'label' => 'Shipping Description',
                        'input' => 'text',
                        'required' => false,
                        'frontend_class' => 'validate-length maximum-length-255',
                        'sort_order' => 150,
                    ],
                    SubscriptionProfile::PROFILE_CURRENCY_CODE => [
                        'type' => 'static',
                        'label' => 'Profile currency code',
                        'input' => 'text',
                        'required' => true,
                        'frontend_class' => 'validate-length maximum-length-255',
                        'sort_order' => 160,
                    ],
                    SubscriptionProfile::IS_VIRTUAL => [
                        'type' => 'static',
                        'label' => 'Is virtual',
                        'sort_order' => 170,
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => true,
                    ],
                    SubscriptionProfile::TOKEN_HASH => [
                        'type' => 'static',
                        'label' => 'Token hash',
                        'input' => 'text',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => 180
                    ],
                    SubscriptionProfile::PAYMENT_ADDITIONAL_INFO => [
                        'type' => 'static',
                        'label' => 'Payment Additional Info',
                        'input' => 'text',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => 190
                    ],
                    SubscriptionProfile::GENERATE_QUOTES_STATE => [
                        'type' => 'static',
                        'label' => 'Generate quotes state',
                        'sort_order' => 200,
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => true,
                    ],
                    SubscriptionProfile::NEED_RECOLLECT => [
                        'type' => 'static',
                        'label' => 'Need Recollect',
                        'sort_order' => 210,
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => true,
                    ],
                    SubscriptionProfile::CANCEL_BEFORE_NEXT_CYCLE => [
                        'type' => 'static',
                        'label' => 'Cancel before next billing cycle',
                        'sort_order' => 220,
                        'input' => 'bool',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => false,
                        'default' => 0,
                    ],
                    SubscriptionProfile::CREATED_AT => [
                        'type' => 'static',
                        'label' => 'Created At',
                        'input' => 'date',
                        'required' => false,
                        'sort_order' => 230,
                        'visible' => false,
                    ],
                    SubscriptionProfile::UPDATED_AT => [
                        'type' => 'static',
                        'label' => 'Updated At',
                        'input' => 'date',
                        'required' => false,
                        'sort_order' => 240,
                        'visible' => false,
                    ],
                ],
            ],
            \TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY => [
                'entity_model' => \TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile::class,
                'table' => \TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY_TABLE,
                'attributes' => [
                    ProductSubscriptionProfile::PARENT_ID => [
                        'type' => 'static',
                        'label' => 'Parent id',
                        'input' => 'int',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => 10,
                    ],
                    ProductSubscriptionProfile::SUBSCRIPTION_PROFILE_ID => [
                        'type' => 'static',
                        'label' => 'Subscription Profile Id',
                        'input' => 'text',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => 20,
                    ],
                    ProductSubscriptionProfile::MAGENTO_PRODUCT_ID => [
                        'type' => 'static',
                        'label' => 'Magento Product Id',
                        'input' => 'text',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => 30,
                    ],
                    ProductSubscriptionProfile::NAME => [
                        'type' => 'static',
                        'label' => 'Magento Product Name',
                        'input' => 'text',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => 40,
                    ],
                    ProductSubscriptionProfile::SKU => [
                        'type' => 'static',
                        'label' => 'Magento Product SKU',
                        'input' => 'text',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => 50,
                    ],
                    ProductSubscriptionProfile::TNW_SUBSCR_UNLOCK_PRESET_QTY => [
                        'type' => 'static',
                        'label' => 'Magento Product subscr unlock preset qty',
                        'input' => 'int',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => 60,
                    ],
                    ProductSubscriptionProfile::PRICE => [
                        'type' => 'static',
                        'label' => 'Price',
                        'input' => 'price',
                        'required' => true,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 70,
                    ],
                    ProductSubscriptionProfile::INITIAL_FEE => [
                        'type' => 'static',
                        'label' => 'Initial Fee',
                        'input' => 'price',
                        'required' => true,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 80,
                    ],
                    ProductSubscriptionProfile::QTY => [
                        'type' => 'static',
                        'label' => 'Qty',
                        'input' => 'text',
                        'required' => true,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 90,
                    ],
                    ProductSubscriptionProfile::PURCHASE_TYPE => [
                        'type' => 'static',
                        'label' => 'Purchase Type',
                        'input' => 'select',
                        'source' => \TNW\Subscriptions\Model\Config\Source\PurchaseType::class,
                        'required' => true,
                        'sort_order' => 100,
                    ],
                    ProductSubscriptionProfile::TRIAL_STATUS => [
                        'type' => 'static',
                        'label' => 'Trial Status',
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => false,
                        'sort_order' => 110,
                    ],
                    ProductSubscriptionProfile::TRIAL_PRICE => [
                        'type' => 'static',
                        'label' => 'Trial Price',
                        'input' => 'price',
                        'required' => false,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 120,
                    ],
                    ProductSubscriptionProfile::LOCK_PRODUCT_PRICE_STATUS => [
                        'type' => 'static',
                        'label' => 'Lock product price',
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => true,
                        'sort_order' => 130,
                    ],
                    ProductSubscriptionProfile::OFFER_FLAT_DISCOUNT_STATUS => [
                        'type' => 'static',
                        'label' => 'Offer flat discount',
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => true,
                        'sort_order' => 140,
                    ],
                    ProductSubscriptionProfile::DISCOUNT_AMOUNT => [
                        'type' => 'static',
                        'label' => 'Discount amount',
                        'input' => 'price',
                        'required' => false,
                        'frontend_class' => 'validate-number',
                        'sort_order' => 150,
                    ],
                    ProductSubscriptionProfile::DISCOUNT_TYPE => [
                        'type' => 'static',
                        'label' => 'Discount type',
                        'input' => 'select',
                        'required' => false,
                        'source' => \TNW\Subscriptions\Model\Config\Source\DiscountType::class,
                        'sort_order' => 160,
                    ],
                    ProductSubscriptionProfile::NEED_RECOLLECT => [
                        'type' => 'static',
                        'label' => 'Need Recollect',
                        'sort_order' => 170,
                        'input' => 'select',
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                        'required' => true,
                    ],
                    ProductSubscriptionProfile::CUSTOM_OPTIONS => [
                        'type' => 'static',
                        'label' => 'Custom options',
                        'input' => 'text',
                        'required' => false,
                        'visible' => false,
                        'sort_order' => 180,
                    ],
                    ProductSubscriptionProfile::CREATED_AT => [
                        'type' => 'static',
                        'input' => 'date',
                        'sort_order' => 190,
                        'visible' => false
                    ],
                    ProductSubscriptionProfile::UPDATED_AT => [
                        'type' => 'static',
                        'input' => 'date',
                        'sort_order' => 200,
                        'visible' => false
                    ]
                ],
            ],
        ];
    }
}
