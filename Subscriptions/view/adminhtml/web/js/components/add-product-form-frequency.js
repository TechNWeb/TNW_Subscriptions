/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-checkbox-set'
], function (Abstract) {
    'use strict';

    return Abstract.extend({

        /**
         * @inheritdoc
         */
        getFrequencyData: function() {
            return this.source.data.product_frequencies;
        },

        /**
         * @inheritdoc
         */
        getProductPrice: function() {
            return this.source.data.product_price;
        },

        /**
         * @inheritdoc
         */
        issetFrequencyPrice: function(frequencyData, optionValue) {
            return (optionValue && frequencyData[optionValue]);
        },

        /**
         * @inheritdoc
         */
        getCurrentFrequencyPrice: function(frequencyData, optionValue) {
            return frequencyData[optionValue]['price']
        }
    })
});
