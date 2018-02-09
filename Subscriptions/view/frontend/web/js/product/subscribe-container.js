/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
            untilCancelledInputSelector: '#term',
            periodControlSelector: '#period-field',
            setQtyFromFrequency: false,
            frequencyInputSelector: 'input[name="billing_frequency"]',
            qtyInputSelector: '#subscribe_qty',
            qtyInputSelectorHidden: '#subscribe_qty_hidden',
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: '.stock.available',
            buttonDisabledClass: 'disabled',
            buttonTextWhileAdding: '',
            buttonTextAdded: '',
            buttonTextDefault: '',
            subscribeTabSelector: '#subscribe-tab-head',
            subscribePriceBlockSelector: 'div.price-subscription_price',
            addToCartTabSelector: '#addtocart-tab-head',
            addToCartPriceBlockSelector: 'div.price-final_price',
            canShowSubscribePriceBlock: false,
            savingsCalculationType: 0,
            product: {},
            selectSimpleProduct: '[name="selected_configurable_option"]',
            childrenSelector: '.super-attribute-select'
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
            this.containers.addToCartPriceBox.show();
            this.containers.subscriptionPriceBox.hide();

            if (this.options.canShowSubscribePriceBlock) {
                this.containers.addToCartPriceBox.hide();
                this.containers.subscriptionPriceBox.show();
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

        /**
         * Event binding
         */
        _bind: function () {
            var widget = this,
                button = $(this.options.subscribeButtonSelector),
                untilCancelledInput = $(this.options.untilCancelledInputSelector),
                frequencyInput = $(this.options.frequencyInputSelector),
                qtyInput = $(this.options.qtyInputSelector),
                childrenSelect = $(this.options.childrenSelector);

            button.on('click', $.proxy(function() {
                widget._submitForm();
            }, this));

            untilCancelledInput.on('change', $.proxy(function() {
                widget._togglePeriod();
            }, this));

            if (this.options.setQtyFromFrequency) {
                frequencyInput.on('change', $.proxy(function () {
                    widget._updateQtyFromFrequency();
                }, this));
            }

            this.containers.subscriptionTab.on('click', $.proxy(function() {
                widget.containers.subscriptionPriceBox.show();
                widget.containers.addToCartPriceBox.hide();
            }, this));

            this.containers.addToCartTab.on('click', $.proxy(function() {
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
            var currentFrequency = $(this.options.frequencyInputSelector + ':checked'),
                qtyInput = $(this.options.qtyInputSelector),
                qtyInputHidden = $(this.options.qtyInputSelectorHidden);
            qtyInput.val(Number(currentFrequency.data('preset-qty')));
            qtyInputHidden.val(Number(currentFrequency.data('preset-qty')));
        },

        /**
         * Update frequency label depends from qty.
         */
        _updateFrequencyLabel: function () {
            var widget = this,
                frequencies = $(this.options.frequencyInputSelector),
                qtyInput = $(this.options.qtyInputSelector),
                qtyValue = qtyInput.val(),
                savingsCalculationType = parseInt(this.options.savingsCalculationType),
                childrenSelect = $(this.options.childrenSelector);
            var isNeedStopCalculating = false;
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
                $.each(option.labels, function (key, label) {
                    var resultLabel = $(label).data('default-label');
                    if (isNeedStopCalculating === false) {
                        var discount = 0;
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
                            resultLabel += $t(saveString)
                                .replace('%p', utils.formatPrice(currentFrequencyPrice, {}))
                                .replace('%s', discount);
                        }
                    }
                    label.innerText = resultLabel;
                });
            });
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

            if (product.type == 'simple' || product.type == 'virtual' || product.type == 'downloadable') {
                result = product.product_price;
            } else if (product.type == 'configurable') {
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
            if (product.type == 'simple' || product.type == 'virtual' || product.type == 'downloadable') {
                result = product.frequency_data[optionValue];
            } else if (product.type == 'configurable') {
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

        /**
         * Show/hide period input
         */
        _togglePeriod: function () {
            var untilCancelledInput = $(this.options.untilCancelledInputSelector),
                periodControl = $(this.options.periodControlSelector);
            if (untilCancelledInput.is(':checked')) {
                periodControl.hide();
            } else {
                periodControl.show();
            }
        },

        /**
         * Submit product form
         */
        _submitForm: function () {
            var self = this,
                form = $(this.options.formSelector);
            
            if (form.validation() && form.validation('isValid')) {

                self.disableCartButton(form);

                $.ajax({
                    url: this.options.subscribeUrl,
                    data: form.serialize(),
                    type: 'post',
                    dataType: 'json',

                    /**
                     * Called when request succeeds
                     *
                     * @param {Object} response
                     */
                    success: function(response) {
                        if (response.redirectUrl) {
                            window.location = response.redirectUrl;
                            return;
                        }
                        /*if (response.message) {
                            $(self.options.messagesSelector).html(response.message);
                        }*/
                        if (response.minicart) {
                            $(self.options.minicartSelector).replaceWith(response.minicart);
                            $(self.options.minicartSelector).trigger('contentUpdated');
                        }
                        if (response.product && response.product.statusText) {
                            $(self.options.productStatusSelector)
                                .removeClass('available')
                                .addClass('unavailable')
                                .find('span')
                                .html(response.product.statusText);
                        }
                        self.enableCartButton(form);
                    }
                });
            }
        },

        disableCartButton: function(form) {
            var textWhileAdding = this.options.buttonTextWhileAdding || $t('Adding...'),
                subscribeButton = $(form).find(this.options.subscribeButtonSelector),
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            subscribeButton.addClass(this.options.buttonDisabledClass);
            subscribeButton.find('span').text(textWhileAdding);
            subscribeButton.attr('title', textWhileAdding);
            addToCartButton.addClass(this.options.buttonDisabledClass);
        },

        enableCartButton: function(form) {
            var textAdded = this.options.buttonTextAdded || $t('Added');
            var self = this,
                subscribeButton = $(form).find(this.options.subscribeButtonSelector),
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            subscribeButton.find('span').text(textAdded);
            subscribeButton.attr('title', textAdded);

            setTimeout(function() {
                var textDefault = self.options.buttonTextDefault || $t('Add to Cart');
                subscribeButton.removeClass(self.options.buttonDisabledClass);
                subscribeButton.find('span').text(textDefault);
                subscribeButton.attr('title', textDefault);
                addToCartButton.removeClass(self.options.buttonDisabledClass);
            }, 1000);
        }
    });

    return $.mage.tnwSubscribeContainer;
});
