<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Product;

/**
 * Class with product attribute codes for subscription.
 */
class Attribute
{
    /**#@+
     * Constants for product attributes.
     */
    const SUBSCRIPTION_PURCHASE_TYPE = 'tnw_subscr_purchase_type';
    const SUBSCRIPTION_TRIAL_STATUS = 'tnw_subscr_trial_status';
    const SUBSCRIPTION_TRIAL_LENGTH = 'tnw_subscr_trial_length';
    const SUBSCRIPTION_TRIAL_LENGTH_UNIT = 'tnw_subscr_trial_length_unit';
    const SUBSCRIPTION_LOCK_PRODUCT_PRICE = 'tnw_subscr_lock_product_price';
    const SUBSCRIPTION_OFFER_FLAT_DISCOUNT = 'tnw_subscr_offer_flat_discount';
    const SUBSCRIPTION_DISCOUNT_AMOUNT = 'tnw_subscr_discount_amount';
    const SUBSCRIPTION_DISCOUNT_TYPE = 'tnw_subscr_discount_type';
    const SUBSCRIPTION_TRIAL_PRICE = 'tnw_subscr_trial_price';
    const SUBSCRIPTION_TRIAL_START_DATE = 'tnw_subscr_trial_start_date';
    const SUBSCRIPTION_START_DATE = 'tnw_subscr_start_date';
    const SUBSCRIPTION_UNLOCK_PRESET_QTY = 'tnw_subscr_unlock_preset_qty';
    const SUBSCRIPTION_SAVINGS_CALCULATION = 'tnw_subscr_savings_calculation';
    const SUBSCRIPTION_INFINITE_SUBSCRIPTIONS = 'tnw_subscr_inf_subscriptions';
    const SUBSCRIPTION_SCHEDULE = 'tnw_subscr_schedule';
    /**#@-*/
}
