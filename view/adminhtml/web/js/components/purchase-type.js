/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

/**
 * Class to handle enabling/disabling subscription attributes fields to made them not required
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry'
], function (Collection, registry) {
    'use strict';

    return Collection.extend({
        defaults: {
            notificationMessage: {
                text: null,
                error: null
            }
        },

        changingVisibility: function () {
            var purchaseType = registry.get('index=tnw_subscr_purchase_type'),
                trialStatusValue = registry.get('index=tnw_subscr_trial_status')
                    ? registry.get('index=tnw_subscr_trial_status').checked()
                    : false,
                lockPriceValue = registry.get('index=tnw_subscr_lock_product_price')
                    ? registry.get('index=tnw_subscr_lock_product_price').checked()
                    : false,
                offerDiscountValue = registry.get('index=tnw_subscr_offer_flat_discount')
                    ? registry.get('index=tnw_subscr_offer_flat_discount').checked()
                    : false,
                discountAmount = registry.get('index=tnw_subscr_discount_amount'),
                trialLength = registry.get('index=tnw_subscr_trial_length'),
                isOneTime = purchaseType.value() == 1;

            this.visible(!isOneTime);
            if (trialLength) {
                trialLength.disabled(isOneTime || !trialStatusValue);
            }
            if (discountAmount) {
                discountAmount.disabled(isOneTime || !lockPriceValue || !offerDiscountValue);
            }
        },

        changedPurchaseType: function () {
            this.changingVisibility();
        },

        changedTrialStatus: function () {
            this.changingVisibility();
        },

        changedOfferDiscount: function () {
            this.changingVisibility();
        },

        changedLockPrice: function () {
            this.changingVisibility();
        },

        render: function (wizard) {
            this.wizard = wizard;
        },

        force: function (wizard) {
        },

        back: function () {
        }
    });
});
