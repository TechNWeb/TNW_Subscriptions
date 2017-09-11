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
            var frequencyPrices = this.getFrequencyPrices();
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
         * Returns current product frequencies prices.
         *
         * @returns {}
         */
        getFrequencyPrices: function() {
            var frequencyPrices = this.source.data.product_frequencies;
            var currentItemData;

            if (this.modifySubscription) {
                currentItemData = this.source.data['item_' + this.item_id];

                if (currentItemData.initial_values
                    && currentItemData.initial_values.billing_frequency
                    && currentItemData.initial_values.price
                    && currentItemData.frequency_data
                    && currentItemData.frequency_data.product_frequencies
                ) {
                    frequencyPrices = currentItemData.frequency_data.product_frequencies;
                    frequencyPrices[currentItemData.initial_values.billing_frequency].price =
                        currentItemData.initial_values.price;
                }
            }

            return frequencyPrices;
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
        },

        /**
         * Sets initial value of the element and subscribes to it's changes.
         */
        setInitialValue: function () {
            var priceFormat = this.getPriceFormat();
            var priceNumber = this.value();
            var priceValue;

            if (typeof this.value() == 'string') {
                priceNumber = formatPrice.formatToNumber(this.value(), priceFormat);
            }

            priceValue = formatPrice.formatPrice(priceNumber, priceFormat);
            this._super();
            this.value(priceValue);
            this.setCompletePreviewLabel(priceValue);

            return this;
        }
    });
});
