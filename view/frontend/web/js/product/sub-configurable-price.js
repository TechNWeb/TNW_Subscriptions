/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'TNW_Subscriptions/js/product/subscribe-price'
], function ($) {
    'use strict';

    $.widget('mage.tnwSubConfigurablePrice', $.mage.tnwSubscribePrice, {
        options: {
            childrenSelector: '.super-attribute-select',
            children: null,
            productAttributesData: {},
            formattedProductAttributesData: []
        },

        /**
         * Initialize widget.
         */
        _create: function() {
            this.formatSuperAttributesData();
            this._super();
            this.setupChangeEvents();
        },

        /**
         * Format super attributes products data.
         */
        formatSuperAttributesData: function() {
            var data = [];
            $.each(this.options.productAttributesData, function (productId, productData) {
                data[productId] = [];
                $.each(productData, function(attributeId, attributeValue) {
                    data[productId][attributeId] = attributeValue;
                })
            });
            this.options.formattedProductAttributesData = data;
        },

        /**
         * Set up .on('change') events for each option element to configure the option price.
         * @private
         */
        setupChangeEvents: function () {
            var widget = this;
            if ($(widget.options.childrenSelector).length) {
                $.each($(widget.options.childrenSelector), $.proxy(function (index, element) {
                    $(element).on('change', function () {
                        widget._insertPriseBox()
                    });
                }));
            } else {
                setTimeout(this.setupChangeEvents.bind(this), 500);
            }
        },

        /**
         * Return selected configurable child id.
         *
         * @return {int|null}
         */
        getSelectedProduct: function() {
            var productAttributesData = this.options.formattedProductAttributesData,
                childrenValues = [],
                selectedProduct = null;

            $.each($(this.options.childrenSelector), function (index, element) {
                var elementName = element.name,
                    attributeId = elementName.slice(16, -1);
                childrenValues[attributeId] = element.value;
            });

            productAttributesData.forEach(function(productData, productId, productAttributesData) {
                var result = productData.every(function(attributeValue, productAttributeId, productData) {

                    return (typeof childrenValues[productAttributeId] != 'undefined')
                        && childrenValues[productAttributeId] === attributeValue;
                });

                if (result) {
                    selectedProduct = productId;
                }
            });

            return selectedProduct;
        },

        /**
         * Insert html price block.
         *
         * @param {string|null} optionIndex
         */
        _insertPriseBox: function (optionIndex) {
            var widget = this;
            if ($(widget.options.childrenSelector).length) {
                var selectedProduct = this.getSelectedProduct(),
                    subscriptionPriceContainer = $(this.options.subscriptionPriceContainerSelector),
                    priceHtml,
                    currentFrequency;
                if (!optionIndex) {
                    currentFrequency = $(this.options.billingFrequencyOptionsSelector + ' option:selected').get(0);
                    if (typeof currentFrequency !== 'undefined') {
                        optionIndex = currentFrequency.value;
                    } else {
                        optionIndex = this.options.subBillingFrequencyId.value;
                    }
                    if (!optionIndex) {
                        return;
                    }
                }

                if (!selectedProduct) {
                    selectedProduct = this.options.subscriptionPricesData['default'][optionIndex];
                }

                if ((typeof this.options.subscriptionPricesData[selectedProduct] !== 'undefined')
                    && (typeof this.options.subscriptionPricesData[selectedProduct][optionIndex] !== 'undefined')
                ) {
                    priceHtml = this.options.subscriptionPricesData[selectedProduct][optionIndex];
                } else {
                    priceHtml = this.options.subscriptionPricesData['config'][optionIndex];
                }

                subscriptionPriceContainer.html(priceHtml);
                $('.subscription-price-with-savings').html(priceHtml);
            } else {
                setTimeout(this._insertPriseBox.bind(this), 500);
            }

        }
    });

    return $.mage.tnwSubConfigurablePrice;
});
