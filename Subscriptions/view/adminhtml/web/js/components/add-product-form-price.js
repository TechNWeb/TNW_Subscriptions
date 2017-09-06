/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-field',
    'TNW_Subscriptions/js/formatPrice',
    'jquery',
    'mage/translate',
    'jquery/ui'
], function (Abstract, formatPrice, $) {
    'use strict';

    return Abstract.extend({
        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.changeValue();
        },

        /**
         * Fires when Billing Frequency or current input is changed.
         *
         * @param value
         */
        changeValue: function (value) {
            var frequencyPrices = this.source.data.product_frequencies;
            var priceFormat = this.getPriceFormat();
            var priceNumber = 0;
            var priceValue = 0;

            if (frequencyPrices && value && frequencyPrices[value]){
                priceNumber = frequencyPrices[value].price;
            } else {
                var currentValue = '0';
                if (this.value()) {
                    currentValue = this.value();
                }
                priceNumber = formatPrice.formatToNumber(currentValue, priceFormat);
            }

            priceValue = formatPrice.formatPrice(priceNumber, priceFormat);
            this.value(priceValue);
        },

        /**
         * Return current price format.
         *
         * @returns {*}
         */
        getPriceFormat: function() {
            var priceFormat = null;
            if (typeof this.priceFormat != 'undefined' && this.priceFormat != null) {
                priceFormat = $.parseJSON(this.priceFormat);
            }

            return priceFormat;
        },

        /**
         * Returns preview label.
         *
         * @returns {boolean, string}
         */
        getPreviewLabel: function () {
            return this.previewLabelVisible ? this.completePreviewLabel() : false;
        }
    });
});
