/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'TNW_Subscriptions/js/formatPrice',
    'jquery',
    'mage/translate'
], function (Abstract, uiRegistry, formatPrice, $j) {
    'use strict';

    return Abstract.extend({
        defaults: {
            notice: $j.mage.__('No estimated savings.')
        },

        /**
         * Sets initial value of the element and subscribes to it's changes.
         */
        setInitialValue: function () {
            this._super();

            var index = this.inputName.replace(/[^\d.]/g, '');

            this.updateFieldEnabling(index);
            this.updatePriceNotice(index);

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();

            var index = this.inputName.replace(/[^\d.]/g, '');

            this.updateFieldEnabling(index);
            this.updatePriceNotice(index);
        },

        updateFieldEnabling: function (index) {
            var lockPrice
                = uiRegistry.get('index=linked').source.data.links.linked[index].tnw_subscr_lock_product_price;

            if (lockPrice == '1') {
                this.disable()
            } else {
                this.enable();
            }
        },

        updatePriceNotice: function (index) {
            var lockPrice
                = uiRegistry.get('index=linked').source.data.links.linked[index].tnw_subscr_lock_product_price;

            var flatDiscount
                = uiRegistry.get('index=linked').source.data.links.linked[index].tnw_subscr_offer_flat_discount;

            if (lockPrice == '1' && flatDiscount == '1') {
                var discount
                    = uiRegistry.get('index=linked').source.data.links.linked[index].tnw_subscr_discount_amount;

                discount = formatPrice.formatPrice(discount);

                var currencySymbol = typeof this.imports.currencySymbol == "undefined"
                    ? this.imports.currencySymbol : '$';

                var discountType
                    = uiRegistry.get('index=linked').source.data.links.linked[index].tnw_subscr_discount_type;

                if (discountType == 1) {  //discount type = Flat Fee
                    discount = currencySymbol + discount;
                } else if (discountType == 2) { //discount type = percent
                    discount = '~' + discount + '%';
                }

                this.notice = $j.mage.__('Estimated ') + discount + $j.mage.__(' savings to the end consumer');

                $j('#' + this.noticeId).html(this.notice);
            }
        }
    });
});
