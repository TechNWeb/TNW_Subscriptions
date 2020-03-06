/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Catalog/js/price-utils',
    'mage/validation'
], function ($, $t, utils) {
    'use strict';

    $.widget('mage.tnwSubscribeContainer', {
        options: {
            formSelector: '#product_addtocart_form',
            addToCartButtonSelector: '#product-addtocart-button',
            subscribeButtonSelector: '#product-subscribe-button',
            subscribeUrl: '#',
            periodControlSelector: '#period-field',
            setQtyFromFrequency: false,
            frequencyInputSelector: 'select[name="billing_frequency"]',
            activeInputSelector: 'input[name="subscribe_active"]',
            qtyInputSelector: '#subscribe_qty',
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: '.stock.available',
            buttonDisabledClass: 'disabled',
            buttonTextWhileAdding: '',
            buttonTextAdded: '',
            buttonTextDefault: '',
            subscribeTabSelector: '#addtocart_subscribe',
            subscribePriceBlockSelector: 'div.price-subscription_price',
            addToCartTabSelector: '#addtocart_onetime',
            addToCartPriceBlockSelector: 'div.price-final_price',
            canShowSubscribePriceBlock: false,
            savingsCalculationType: 0,
            product: {},
            selectSimpleProduct: '[name="selected_configurable_option"]',
            childrenSelector: '.super-attribute-select',
            subscriptionPriceBoxWithSavings: '.subscription-price-with-savings',
            oneTimePriceBox: '.onetime-final-price'
        },

        containers: {
            subscriptionTab: null,
            subscriptionPriceBox: null,
            addToCartTab: null,
            addToCartPriceBox: null
        },

        /**
         * Initialize widget
         */
        _create: function () {
            this._prepareContainers();
            this._initialize();
            this._bind();
        },

        /**
         * First initialization.
         */
        _initialize: function () {
            this._updateFrequencyLabel();
            this._updateAuxPrices();
            this.containers.addToCartPriceBox.show();
            this.containers.subscriptionPriceBox.hide();

            if (this.options.canShowSubscribePriceBlock) {
                this.containers.addToCartPriceBox.hide();
                this.containers.subscriptionPriceBox.show();
            }
        },

        _updateAuxPrices: function() {
            var product = this.options.product,
                priceWithSaving = $(this.options.frequencyInputSelector + ' option:selected').data('price-with-saving');
            $(this.options.oneTimePriceBox).html(this.containers.addToCartPriceBox.html());
            if (product.type === 'simple' || product.type === 'virtual' || product.type === 'downloadable') {
                $(this.options.subscriptionPriceBoxWithSavings).html(priceWithSaving);
            }
        },

        /**
         * Get html elements.
         */
        _prepareContainers: function () {
            this.containers.subscriptionTab = $(this.options.subscribeTabSelector);
            this.containers.subscriptionPriceBox = $(this.options.subscribePriceBlockSelector);
            this.containers.addToCartTab = $(this.options.addToCartTabSelector);
            this.containers.addToCartPriceBox = $(this.options.addToCartPriceBlockSelector);
        },

        _bindSuperAttributesEvent: function() {
            var widget = this;

            if ($(widget.options.childrenSelector).length) {
                $.each($(widget.options.childrenSelector), $.proxy(function (index, element) {
                    $(element).on('change', function () {
                        widget._updateAuxPrices();
                    });
                }));
            } else {
                setTimeout(this._bindSuperAttributesEvent.bind(this), 500);
            }
        },

        /**
         * Event binding
         */
        _bind: function () {
            var widget = this,
                frequencyInput = $(this.options.frequencyInputSelector),
                activeInput = $(this.options.activeInputSelector),
                qtyInput = $(this.options.qtyInputSelector),
                childrenSelect = $(this.options.childrenSelector),
                changeDeliveryDateLink = $('.action.change-delivery-date'),
                updateDeliverDateButton = $('.action.update-delivery-date'),
                startOnWrapper = $('.start-on-wrapper'),
                startOnFormattedInput = $('#start_on_alt');

            this._bindSuperAttributesEvent();

            changeDeliveryDateLink.on('click', function (e) {
                e.stopImmediatePropagation();
                e.preventDefault();
                startOnWrapper.show();
                changeDeliveryDateLink.hide();
            });

            updateDeliverDateButton.on('click', function (e) {
                e.stopImmediatePropagation();
                e.preventDefault();
                changeDeliveryDateLink.show();
                startOnWrapper.hide();
                $('.delivery-schedule-date').html(startOnFormattedInput.val());
            });

            frequencyInput.on('change', $.proxy(function () {
                if (this.options.setQtyFromFrequency) {
                    widget._updateQtyFromFrequency();
                }
                widget._updateAuxPrices();
            }, this));

            this.containers.subscriptionTab.on('click', $.proxy(function() {
                activeInput.val(1);
                widget.containers.subscriptionPriceBox.show();
                widget.containers.addToCartPriceBox.hide();
            }, this));

            this.containers.addToCartTab.on('click', $.proxy(function() {
                activeInput.val(0);
                widget.containers.subscriptionPriceBox.hide();
                widget.containers.addToCartPriceBox.show();
            }, this));

            qtyInput.on('change', $.proxy(function () {
                widget._updateFrequencyLabel();
            }, this));

            if (childrenSelect) {
                childrenSelect.on('change', $.proxy(function () {
                    widget._updateFrequencyLabel();
                }, this));
            }
        },

        /**
         * Set preset qty from frequency into qty input
         */
        _updateQtyFromFrequency: function () {
            var currentFrequency = $(this.options.frequencyInputSelector + ' option:selected'),
                qtyInput = $(this.options.qtyInputSelector);
            qtyInput.val(Number(currentFrequency.data('preset-qty')));
        },

        /**
         * Update frequency label depends from qty.
         */
        _updateFrequencyLabel: function () {
            var widget = this,
                frequencies = $(this.options.frequencyInputSelector + ' option'),
                qtyInput = $(this.options.qtyInputSelector),
                qtyValue = qtyInput.val(),
                savingsCalculationType = parseInt(this.options.savingsCalculationType),
                childrenSelect = $(this.options.childrenSelector),
                isNeedStopCalculating = false,
                resultLabel = '';
            if (childrenSelect) {
                $.each(childrenSelect, function (key, child) {
                     if (!$(child).val()) {
                         isNeedStopCalculating = true;
                         return false;
                     }
                });
            }
            $.each(frequencies, function (key, option) {
                var currentFrequencyPrice = parseFloat(widget.getFrequencyPrice(parseInt($(option).val()))),
                    productPrice = parseFloat(widget.getProductPrice()),
                    frequencyUnit = $(option).data('frequency-unit'),
                    presetQty = $(option).data('preset-qty'),
                    frequencyUnitType = $(option).data('frequency-unit-type'),
                    calculatedUnit = widget.getCalculatedUnit(frequencyUnit, frequencyUnitType),
                    saveString = ' %p (SAVE ~%s%)';

                resultLabel = $(option).data('default-label');
                if (isNeedStopCalculating === false) {
                    var discount = 0,
                        priceWithSaving = '';
                    qtyValue = presetQty ? presetQty : qtyValue;
                    if (savingsCalculationType === 2) {
                        //formula for service
                        discount = ((productPrice * calculatedUnit - currentFrequencyPrice) * qtyValue * 100)
                            / (productPrice * calculatedUnit);
                    } else if (savingsCalculationType === 1) {
                        //formula for any retail / physical product with preset qty
                        discount = ((productPrice * qtyValue - currentFrequencyPrice) * 100)
                            / (productPrice * qtyValue);
                    } else {
                        //formula for any retail / physical product
                        discount = ((productPrice - currentFrequencyPrice) * qtyValue * 100) / productPrice;
                    }
                    discount = parseInt(discount);
                    if (discount > 0) {
                        priceWithSaving = $t(saveString)
                        .replace('%p', utils.formatPrice(currentFrequencyPrice, {}))
                        .replace('%s', discount);
                        resultLabel += priceWithSaving;
                    } else {
                        priceWithSaving = utils.formatPrice(currentFrequencyPrice, {});
                    }

                    if (widget.getProductTrialLabel()) {
                        $(option).data('price-with-saving', widget.getProductTrialLabel());
                    } else {
                        $(option).data('price-with-saving', priceWithSaving);
                    }
                }
                option.innerText = resultLabel;
            });
        },

        /**
         * Returns product trial label
         * @returns {boolean|string}
         */
        getProductTrialLabel: function() {
            var product = this.options.product,
                trialLabelString = $t('Try for %p %u%p'),
                trialData = product.trial_data,
                trialPrice = $t(' FREE');
            if (
                (product.type === 'simple' || product.type === 'virtual' || product.type === 'downloadable')
                && trialData
            ) {
                trialPrice = trialData.trial_price
                    ? $t(', starting at ') + utils.formatPrice(trialData.trial_price, {})
                    : trialPrice;
                return trialLabelString
                    .replace('%p', trialData.trial_length)
                    .replace('%u', trialData.trial_label)
                    .replace('%p', trialPrice)
            }
            return false;
        },

        /**
         * Returns product price.
         *
         * @returns {number}
         */
        getProductPrice: function () {
            var product = this.options.product,
                selectedProduct,
                selectedValue,
                result = 0;

            if (product.type === 'simple' || product.type === 'virtual' || product.type === 'downloadable') {
                result = product.product_price;
            } else if (product.type === 'configurable') {
                selectedProduct = $(this.options.selectSimpleProduct).val();
                selectedValue = $(this.options.childrenSelector).val();

                if (selectedProduct && selectedValue) {
                    result = product.children[selectedProduct].product_price;
                }
            }

            return result;
        },

        /**
         * Returns product frequency price.
         *
         * @param optionValue
         * @returns {number}
         */
        getFrequencyPrice: function (optionValue) {
            var product = this.options.product,
                result = 0,
                selectedProduct,
                selectedValue,
                frequencyData;
            if (product.type === 'simple' || product.type === 'virtual' || product.type === 'downloadable') {
                result = product.frequency_data[optionValue];
            } else if (product.type === 'configurable') {
                selectedProduct = parseInt($(this.options.selectSimpleProduct).val());
                selectedValue = $(this.options.childrenSelector).val();
                if (selectedProduct && selectedValue) {
                    frequencyData = product.children[selectedProduct].frequency_data;
                    if (frequencyData) {
                        result = product.children[selectedProduct].frequency_data[optionValue];
                        if (result <= 0) {
                            result = product.frequency_data[optionValue];
                        }
                    }
                }
            }

            return result;
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
            if (frequencyUnitType === 3) {
                //type day
                result = frequencyUnit;
            }else if (frequencyUnitType === 5) {
                //type month
                result = frequencyUnit * 30;
            }

            return result;
        },
    });

    return $.mage.tnwSubscribeContainer;
});
