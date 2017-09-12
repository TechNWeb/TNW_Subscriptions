/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/checkbox-set',
    'TNW_Subscriptions/js/formatPrice',
    'underscore',
    'jquery',
    'uiRegistry',
    'mage/translate',
    'jquery/ui'
], function (Abstract, formatPrice, _, $, registry) {
    'use strict';

    return Abstract.extend({
        defaults: {
            showPreview: false,
            previewElementTmpl: 'TNW_Subscriptions/form/element/template/preview-label',
            listens: {
                showPreview: 'onShowPreviewChanged'
            },
            parentForm: null,
            currencySymbol: '',
            optionsForLabel: {}
        },

        initialize: function () {
            this._super();
            this.getOptionsForLabel();
            this.setDiscountLabel('all');

            return this;
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super().
            observe(['showPreview','options']);

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.setDiscountLabel('all');
        },

        /**
         * Callback that fires when frequency price value property is updated.
         */
        onPriceUpdate: function(priceValue) {
            this.setDiscountLabel(priceValue);
        },

        getOptionsForLabel: function() {
            var options = this.options(),
                optionsForLabel = {};

            options.forEach(function(option, index, arr) {
                optionsForLabel[option.value] = option.label;
            });

            this.optionsForLabel = optionsForLabel;

            return this.optionsForLabel;
        },

        /**
         * Returns preview label.
         *
         * @returns {string}
         */
        getPreviewLabel: function () {
            var label = this.optionsForLabel[this.value()];

            return label ? label: '';
        },

        /**
         * Resets value if "showPreview" property changed.
         *
         * @param value
         */
        onShowPreviewChanged: function (value) {
            if (value && this.initialValue && this.value() !== this.initialValue){
                this.reset();
            }
        },

        /**
         * Returns current option label.
         *
         * @param value
         * @returns string
         */
        getCurrentLabel: function (value) {
            return this.getOption(value).label;
        },

        /**
         * Returns current option by option value.
         *
         * @param value
         * @returns {*}
         */
        getOption: function(value) {
            var optionIndex = _.findIndex(this.options(), {value: value});

            return this.options()[optionIndex];
        },

        /**
         * Changes label for all options if frequency price for options less then product price.
         * Also deletes discount data if frequency price for options bigger then product price.
         *
         * @param value
         * @returns {}
         */
        setDiscountLabel: function (value) {
            var frequencyData = this.getFrequencyData(),
                options = this.options(),
                optionsToShow = options,
                self = this;

            if (frequencyData){
                if (value == 'all') {
                    options.forEach(function(option, index, arr) {
                        optionsToShow[index]['label'] = self.changeOptionLabel(option, index, value);
                    });
                } else {
                    var currentValue = this.value();
                    var currentOption = this.getOption(currentValue);
                    var optionIndex = _.findIndex(options, {value: currentValue});
                    optionsToShow[optionIndex]['label'] = this.changeOptionLabel(currentOption, optionIndex, value);
                }

                this.options(optionsToShow);
            }

            return this;
        },

        /**
         * Changes option label according to params (adds SAVE message to label).
         *
         * @param option
         * @param optionIndex
         * @param changeType
         * @returns {*}
         */
        changeOptionLabel: function(option, optionIndex, changeType) {
            var optionValue = option.value,
                frequencyLabel = this.optionsForLabel[optionValue],
                frequencyData = this.getFrequencyData(),
                discount = 0,
                productPrice = this.getProductPrice(),
                priceFormat = this.getPriceFormat(),
                currentFrequencyPrice;

            if (this.issetFrequencyPrice(frequencyData, optionValue)) {
                    currentFrequencyPrice = this.getCurrentFrequencyPrice(frequencyData, optionValue);
                if (changeType != 'all') {
                    currentFrequencyPrice = formatPrice.formatToNumber(changeType, priceFormat);
                }

                discount = productPrice - currentFrequencyPrice;

                if (discount > 0) {
                    discount = this.currencySymbol + formatPrice.formatPrice(discount, priceFormat);
                    frequencyLabel += '  ' + $.mage.__('(SAVE %s)').replace('%s', discount);
                }

                return frequencyLabel;
            }
        },

        /**
         * Returns current frequency data.
         *
         * @returns {*}
         */
        getFrequencyData: function() {
            var result,
                currentItemData = this.getCurrentItemData();

            if (currentItemData) {
                result = currentItemData.frequency_data.product_frequencies;
            }

            return result;
        },

        /**
         * Returns current product price.
         *
         * @returns {*}
         */
        getProductPrice: function() {
            var result,
            currentItemData = this.getCurrentItemData();

            if (currentItemData) {
                result = currentItemData.product_price;
            }

            return result;
        },

        /**
         * Checks if isset price of frequency from params.
         *
         * @param frequencyData
         * @param optionValue
         * @returns bool|number
         */
        issetFrequencyPrice: function(frequencyData, optionValue) {
            var currentItemData = this.getCurrentItemData(),
                issetFrequencyPrice;

            if (this.frequencyIsInitial(optionValue, currentItemData)) {
                issetFrequencyPrice = currentItemData.price;
            } else {
                issetFrequencyPrice = (optionValue && frequencyData[optionValue]);
            }
            return issetFrequencyPrice
        },

        /**
         * Returns price of frequency from params.
         *
         * @param frequencyData
         * @param optionValue
         * @returns number|string
         */
        getCurrentFrequencyPrice: function(frequencyData, optionValue) {
            var price,
                currentItemData = this.getCurrentItemData();

            if (this.frequencyIsInitial(optionValue, currentItemData)) {
                price = currentItemData.price;
            } else {
                price = frequencyData[optionValue]['price'];
            }

            return price;
        },

        /**
         * Check if frequency in params is initial for current item.
         *
         * @param optionValue
         * @param currentItemData
         * @returns {boolean}
         */
        frequencyIsInitial: function(optionValue, currentItemData) {
            var initialFrequencyId = currentItemData.billing_frequency;

            return (initialFrequencyId * 1 == optionValue * 1);
        },

        /**
         * Returns current item data.
         *
         * @returns {}
         */
        getCurrentItemData: function() {
            var result = Array;
            if (this.parentForm) {
                var parent = registry.get(this.parentForm);
                if (parent) {
                    result = parent.source.data['item_' + parent.objectItemId];
                }
            }

            return result;
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
        }
    });
});
