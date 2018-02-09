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
            labelsForOptions: {}
        },

        /**
         * @inheritDoc
         */
        initialize: function () {
            this._super();
            this.getLabelsForOptions();
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
         *
         * @return void
         */
        onUpdate: function () {
            this._super();
            this.setDiscountLabel('all');
        },

        /**
         * Callback that fires when frequency price value property is updated.
         *
         * @return void
         */
        onPriceUpdate: function() {
            this.setDiscountLabel('all');
        },

        /**
         * Change current grid item price label.
         *
         * @return void
         */
        changeItemPriceLabel: function(value) {
            this.setDiscountLabel('all');
        },


        /**
         * Return options labels.
         *
         * @return {Object}
         */
        getLabelsForOptions: function() {
            if (Object.keys(this.labelsForOptions).length == 0) {
                var options = this.options(),
                    labelsForOptions = {};

                options.forEach(function(option, index, arr) {
                    labelsForOptions[option.value] = option.label;
                });

                this.labelsForOptions = labelsForOptions;
            }

            return this.labelsForOptions;
        },

        /**
         * Return option label.
         *
         * @param {string|integer} value
         * @return string
         */
        getLabelForOption: function(value) {
            var labels = this.getLabelsForOptions();

            return labels[value];
        },

        /**
         * Returns preview label.
         *
         * @returns {string}
         */
        getPreviewLabel: function () {
            var label = this.getLabelForOption(this.value());

            return label ? label: '';
        },

        /**
         * Resets value if "showPreview" property changed.
         *
         * @param value
         * @return void
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
         * @return string
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
         * @returns {Object}
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
        changeOptionLabel: function (option, optionIndex, changeType) {
            var optionValue = option.value,
                frequencyLabel = this.getLabelForOption(optionValue),
                frequencyData = this.getFrequencyData(),
                discount = 0,
                productPrice = parseFloat(this.getProductPrice()),
                priceFormat = this.getPriceFormat(),
                productQty = this.getProductQty(),
                currentFrequencyPrice,
                saveString = ' %p (SAVE ~%s%)';
            if (this.issetFrequencyPrice(frequencyData, optionValue)) {
                var frequencyUnit = frequencyData[optionValue].frequency_unit,
                    frequencyUnitType = frequencyData[optionValue].frequency_unit_type,
                    presetQty = frequencyData[optionValue].preset_qty,
                    calculatedUnit = this.getCalculatedUnit(frequencyUnit, frequencyUnitType);
                var inputFrequencyPrice = this.getCurrentFrequencyPrice(frequencyData, optionValue);
                currentFrequencyPrice = parseFloat(formatPrice.formatToNumber(inputFrequencyPrice, priceFormat));
                if (changeType !== 'all' && currentFrequencyPrice <= 0) {
                    currentFrequencyPrice = formatPrice.formatToNumber(changeType, priceFormat);
                }
                currentFrequencyPrice = parseFloat(currentFrequencyPrice);
                productQty = presetQty ? presetQty : productQty;
                if (this.getSavingsCalculationType() === 2) {
                    //formula for service
                    discount = ((productPrice * calculatedUnit - currentFrequencyPrice) * productQty * 100)
                        / (productPrice * calculatedUnit);
                } else if (this.getSavingsCalculationType() === 1) {
                    //formula for any retail / physical product with preset qty
                    discount = ((productPrice * productQty - currentFrequencyPrice) * 100)
                        / (productPrice * productQty);
                } else {
                    //formula for any retail / physical product
                    discount = ((productPrice - currentFrequencyPrice) * productQty * 100) / productPrice;
                }
                discount = parseInt(discount);
                if (discount > 0) {
                    frequencyLabel += $.mage.__(saveString)
                        .replace('%p', formatPrice.formatPrice(currentFrequencyPrice, priceFormat))
                        .replace('%s', discount);
                }

                return frequencyLabel;
            }
        },

        /**
         * Returns calculated frequency unit.
         *
         * @param {number} frequencyUnit
         * @param {number} frequencyUnitType
         * @returns {number}
         */
        getCalculatedUnit: function (frequencyUnit, frequencyUnitType) {
            var result = 0;
            if (frequencyUnitType == 3) {
                //type day
                result = frequencyUnit;
            } else if (frequencyUnitType == 5) {
                //type month
                result = frequencyUnit * 30;
            }

            return result;
        },

        /**
         * Returns algorithm type for savings calculation.
         *
         * @returns {number}
         */
        getSavingsCalculationType: function () {
            var type = 0,
                currentItemData = this.getCurrentItemData();
            if (currentItemData.savings_calculation) {
                type = parseInt(currentItemData.savings_calculation);
            }

            return type;
        },

        /**
         * Returns product qty.
         *
         * @returns {number}
         */
        getProductQty: function () {
            var qty = 0,
                currentItemData = this.getCurrentItemData();
            if (currentItemData.qty) {
                qty = currentItemData.qty;
            }

            return qty;
        },

        /**
         * Returns current frequency data.
         *
         * @returns {*}
         */
        getFrequencyData: function() {
            var result,
                currentItemData = this.getCurrentItemData();

            if (currentItemData && currentItemData.frequency_data) {
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
            var result = [];
            if (this.parentForm) {
                var parent = registry.get(this.parentForm);
                if (parent) {
                    result = parent.source.data['item_' + parent.additionalData.objectItemId];
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
