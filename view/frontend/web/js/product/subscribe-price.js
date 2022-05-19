/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'underscore',
    'Magento_Catalog/js/price-utils'
], function ($, $t, _, priceUtils) {
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
                    priceHtml = $t('There is no auto-ship available for this option');
                } else {
                    productId = _.toArray(this.options.subscriptionPricesData['default']).slice(0, 1);
                    priceHtml = _.toArray(this.options.subscriptionPricesData[productId]).slice(0, 1);
                }
            }

            this.element.find(this.options.subscriptionPriceContainerSelector).html(priceHtml);
            $(this.options.alternativeContainerSelector).html(priceHtml);
        },

        updateBundlePrices: function (price, oldPrice, frequencies, trialData) {
            _.each(this.options.subscriptionPricesData, function (priceHtml, frequencyId, data) {
                var bundlePrice = $('<div/>').html(priceHtml),
                    trial = frequencyId.match(/(^[0-9]+)_trial/),
                    initialFee = trial
                        ? parseFloat(frequencies[trial[1]].initial_fee)
                        : parseFloat(frequencies[frequencyId].initial_fee),
                    frequencyPrice = trial
                        ? parseFloat(frequencies[trial[1]].price)
                        : parseFloat(frequencies[frequencyId].price),
                    trialPrice = trial ? parseFloat(trialData.trial_price) : false

                bundlePrice.find('.subscription-price-container .price')
                    .html(priceUtils.formatPrice(
                        price + (trial ? trialPrice : frequencyPrice) + initialFee,
                        {},
                        false
                    ))
                bundlePrice.find('.subscription-price-bottom-messages .price')
                    .html(priceUtils.formatPrice(price + frequencyPrice, {}, false))

                bundlePrice.find('.old-price.main').toggle(oldPrice > price + frequencyPrice).find('.price')
                    .html(priceUtils.formatPrice(oldPrice, {}, false))

                data[frequencyId] = bundlePrice.contents()
            })
        }
    });
    return $.mage.tnwSubscribePrice;
});
