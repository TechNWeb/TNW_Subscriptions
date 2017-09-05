/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({

        /**
         * Fires when Billing Frequency is changed.
         *
         * @param value
         */
        changeValue: function (value) {
            var frequencyPrices = this.source.data.product_frequencies;
            var initialFeeValue = 0;
            var visible = true;

            if (frequencyPrices && value && frequencyPrices[value]){
                this.value(frequencyPrices[value].initial_fee);
            }

            if (this.value() == 0) {
                visible = false;
            }
            this.visible(visible);
        }
    })
});
