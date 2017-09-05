/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-field',
    'TNW_Subscriptions/js/formatPrice',
    'mage/translate',
    'jquery/ui'
], function (Abstract, formatPrice) {
    'use strict';

    return Abstract.extend({
        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.changeValue();
        },


        changeValue: function (value) {
            var frequencyPrices = this.source.data.product_frequencies;

            if (frequencyPrices && value && frequencyPrices[value]){
                this.value(frequencyPrices[value].price);
            }
        },

        /**
         * Returns preview label.
         *
         * @returns {boolean, string}
         */
        getPreviewLabel: function () {
            return this.previewLabelVisible ? this.completePreviewLabel() : false;
        }
    });
});
