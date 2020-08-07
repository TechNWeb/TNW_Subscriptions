/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-field',
    'TNW_Subscriptions/js/formatPrice',
    'jquery',
    'uiRegistry',
    'mage/translate',
    'jquery/ui'
], function (Abstract, formatPrice, $, registry, $t) {
    'use strict';

    return Abstract.extend({
        defaults: {
            parentForm: null
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this.bubble('update', this.hasChanged());
            this.validate();
            this.changeValue();
            if (this.getPriceInclTax()) {
                this.setCompletePreviewLabel(this.getPriceInclTax());
            }
        },

        /**
         * Fires when Billing Frequency or current input is changed.
         *
         * @param value
         */
        changeValue: function (value) {
            var frequencyPrices = this.getFrequencyPrices(),
                priceFormat = this.getPriceFormat(),
                currentValue = 0;
            priceFormat.pattern = '%s';
            if (frequencyPrices && value && frequencyPrices[value]){
                currentValue = frequencyPrices[value].price;
            } else if (this.value()) {
                currentValue = formatPrice.formatToNumber(this.value(), priceFormat);
            }

            currentValue = formatPrice.formatPrice(currentValue, priceFormat);
            this.value(currentValue);
        },

        /**
         * Returns current product frequencies prices.
         *
         * @returns {}
         */
        getFrequencyPrices: function() {
            var frequencyPrices = this.source.data.product_frequencies,
                currentItemData,
                parent;

            if (frequencyPrices === undefined) {
                var sourceParts = this.parentScope.split('.');
                var itemData = this.source[sourceParts[0]][sourceParts[1]];
                if (
                    itemData !== undefined
                    && (
                        this.source.data.billing_frequency_id !== itemData.billing_frequency
                        || this.source.data.changed_price
                    )
                ) {
                    frequencyPrices =itemData.frequency_data.product_frequencies;
                    this.source.data.changed_price = true;
                }
            }
            if (this.modifySubscription && this.getParentForm()) {
                parent = this.getParentForm();
                if (parent) {
                    currentItemData = parent.source.data['item_' + parent.additionalData.objectItemId];
                }

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

        getParentForm: function() {
            var parent = null;
            if (this.parentForm) {
                parent = registry.get(this.parentForm);
            }

            return parent;
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
         * Get price including tax.
         * @returns {string|boolean}
         */
        getPriceInclTax: function() {
            var priceInclTax = this.source.get(this.parentScope + '.price_incl_tax'),
                priceFormat = this.getPriceFormat();
            priceFormat.pattern = '%s';
            if (!priceInclTax) return false;
            priceInclTax = formatPrice.formatToNumber(priceInclTax, priceFormat);
            priceInclTax = formatPrice.formatPrice(priceInclTax, priceFormat) + ' <span class="tax-label">(' + $t('incl. tax') + ')</span>';
            return priceInclTax;
        },

        /**
         * Sets initial value of the element and subscribes to it's changes.
         */
        setInitialValue: function() {
            this._super();
            if (this.getPriceInclTax()) {
                this.setCompletePreviewLabel(this.getPriceInclTax());
            }
            return this;
        }
    });
});
