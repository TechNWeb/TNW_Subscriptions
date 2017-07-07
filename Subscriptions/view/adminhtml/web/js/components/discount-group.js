/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/group',
    'uiRegistry'
], function (Collection, registry) {
    'use strict';

    return Collection.extend({

        changingVisibility: function () {
            var isVisible = false;
            var lockPriceComponent = registry.get('index=tnw_subscr_lock_product_price');
            var offerDiscountComponent = registry.get('index=tnw_subscr_offer_flat_discount');

            if (lockPriceComponent.checked() && offerDiscountComponent.checked()) {
                isVisible = true;
            }

            this.visible(isVisible);
        },

        changedOfferDiscount: function () {
            this.changingVisibility();
        },

        changedLockPrice: function () {
            this.changingVisibility();
        }
    });
});
