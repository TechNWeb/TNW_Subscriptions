/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    $.widget('mage.tnwSubscribePrice', {
        options: {
            childrenSelector: '.super-attribute-select',
            subscriptionPriceContainerSelector: '.price-subscription_price',
            alternativeContainerSelector: '.subscription-price-with-savings',
            productAttributesData: {}
        },

        /**
         *
         * @param optionIndex
         * @param selectedProduct
         * @param showTrial
         * @private
         */
        _insertPriseBox: function (optionIndex, selectedProduct, showTrial = true) {
            var priceHtml,
                productId;

            // If simple
            if (!this.options.subscriptionPricesData['default']) {
                if (!optionIndex) {
                    return;
                }
                priceHtml = (showTrial && this.options.subscriptionPricesData[optionIndex + '_trial'])
                    ? this.options.subscriptionPricesData[optionIndex + '_trial']
                    : this.options.subscriptionPricesData[optionIndex];
            // If configurable
            } else {
                if (selectedProduct && !!optionIndex) {
                    priceHtml =
                        (showTrial && this.options.subscriptionPricesData[selectedProduct][optionIndex + '_trial'])
                            ? this.options.subscriptionPricesData[selectedProduct][optionIndex + '_trial']
                            : this.options.subscriptionPricesData[selectedProduct][optionIndex];
                } else if (selectedProduct && !optionIndex) {
                    priceHtml = $t('There is no subscription available for this option');
                } else {
                    productId = _.toArray(this.options.subscriptionPricesData['default']).slice(0, 1);
                    priceHtml = _.toArray(this.options.subscriptionPricesData[productId]).slice(0, 1);
                }
            }

            $(this.options.subscriptionPriceContainerSelector).html(priceHtml);
            $(this.options.alternativeContainerSelector).html(priceHtml);
        }
    });
    return $.mage.tnwSubscribePrice;
});
