/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
], function ($j) {
    'use strict';

    $j.widget('mage.tnwSubscribePrice', {
        options: {
            subscriptionPricesData: {},
            subscriptionPriceContainerSelector: '.price-subscription_price',
            billingFrequencyOptionsSelector: "input[name='billing_frequency']",
            subBillingFrequencyId: {
                value : 0
            }
        },

        /**
         * Initialize widget.
         */
        _create: function () {
            this._initialize();
            this._bind();
        },

        /**
         * First initialization.
         */
        _initialize: function () {
            var currentFrequency = this.options.subBillingFrequencyId;

            if (typeof currentFrequency == 'undefined' || currentFrequency.value == 0) {
                currentFrequency = $j(this.options.billingFrequencyOptionsSelector + ':checked').get(0);
            }

            if (currentFrequency !== undefined) {
                this._insertPriseBox(currentFrequency.value);
            }
        },

        /**
         * Event binding.
         */
        _bind: function () {
            var widget = this;

            $j(this.options.billingFrequencyOptionsSelector).on('change', function () {
                widget._insertPriseBox(this.value);
            });

        },

        /**
         * Insert html price block.
         *
         * @param optionIndex
         */
        _insertPriseBox: function (optionIndex) {
            if (!optionIndex) {
                return;
            }

            var subscriptionPriceContainer = $j(this.options.subscriptionPriceContainerSelector);
            $j(subscriptionPriceContainer).html(this.options.subscriptionPricesData[optionIndex]);
        }
    });

    return $j.mage.tnwSubscribePrice;
});
