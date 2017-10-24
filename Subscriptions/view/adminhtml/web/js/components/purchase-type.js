/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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

        changingVisibility: function () {
            var purchaseType = registry.get('index=tnw_subscr_purchase_type'),
                trialStatus = registry.get('index=tnw_subscr_trial_status'),
                trialLength = registry.get('index=tnw_subscr_trial_length'),
                lockPrice = registry.get('index=tnw_subscr_lock_product_price'),
                offerDiscount = registry.get('index=tnw_subscr_offer_flat_discount'),
                discountAmount = registry.get('index=tnw_subscr_discount_amount'),
                isOneTime = purchaseType.value() == 1;

            this.visible(!isOneTime);
            trialLength.disabled(isOneTime || !trialStatus.checked());
            discountAmount.disabled(isOneTime || !lockPrice.checked() || !offerDiscount.checked());
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
        }
    });
});
