/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-checkbox-set',
    'uiRegistry'
], function (Abstract, registry) {
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
        },

        /**
         * @inheritdoc
         */
        getProductQty: function () {
            var qty = 0,
                mainForm = registry.get('index=' + this.ns).getModalForm();

            if (mainForm && mainForm.configurableData && mainForm.configurableData.qty) {
                qty = mainForm.configurableData.qty;
            }

            return qty;
        },

        /**
         * @inheritdoc
         */
        getSavingsCalculationType: function () {
            return  this.source.data.savings_calculation;
        }
    })
});
