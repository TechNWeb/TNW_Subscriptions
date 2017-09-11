/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/checkbox-set',
    'TNW_Subscriptions/js/formatPrice',
    'underscore',
    'jquery',
    'mage/translate',
    'jquery/ui'
], function (Abstract, formatPrice, _, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            showPreview: false,
            previewElementTmpl: 'TNW_Subscriptions/form/element/template/preview-label',
            listens: {
                showPreview: 'onShowPreviewChanged'
            }
        },

        initialize: function () {
            this._super()
                .setDiscountLabel('all');

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

        /**
         * Returns preview label.
         *
         * @returns {string}
         */
        getPreviewLabel: function () {
            var optionIndex = _.findIndex(this.optionsForLabel, {value: this.value()});
            var option = this.optionsForLabel[optionIndex];

            return option ? option.label : '';
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
            var frequencyLabel = this.optionsForLabel[optionIndex].label;
            var optionValue = option.value;
            var frequencyData = this.getFrequencyData();
            var discount = 0;
            var productPrice = this.getProductPrice();
            var priceFormat = this.getPriceFormat();

            if (this.issetFrequencyPrice(frequencyData, optionValue)) {
                var currentFrequencyPrice = this.getCurrentFrequencyPrice(frequencyData, optionValue);
                if (changeType != 'all') {
                    currentFrequencyPrice = formatPrice.formatToNumber(changeType, priceFormat);
                }

                discount = productPrice - currentFrequencyPrice;

                if (discount > 0) {
                    discount = this.addbefore + formatPrice.formatPrice(discount, priceFormat);
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
            var currentItemData = this.getCurrentItemData();

            return currentItemData.frequency_data.product_frequencies;
        },

        /**
         * Returns current product price.
         *
         * @returns {*}
         */
        getProductPrice: function() {
            var currentItemData = this.getCurrentItemData();

            return currentItemData.product_price;
        },

        /**
         * Checks if isset price of frequency from params.
         *
         * @param frequencyData
         * @param optionValue
         * @returns bool|number
         */
        issetFrequencyPrice: function(frequencyData, optionValue) {
            var currentItemData = this.getCurrentItemData();
            var issetFrequencyPrice;

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
            var price;
            var currentItemData = this.getCurrentItemData();

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
            return this.source.data['item_' + this.item_id];
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
