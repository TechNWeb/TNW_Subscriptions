/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
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
                this.value(frequencyPrices[value]);
            }
        }
    });
});
