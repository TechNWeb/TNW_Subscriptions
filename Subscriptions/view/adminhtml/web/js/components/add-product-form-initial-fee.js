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
            var frequenciesData = this.source.data.product_frequencies;
            var visible = true;

            if (this.modifySubscription) {
                frequenciesData = this.source.data['item_' + this.item_id].frequency_data.product_frequencies;
            }

            if (frequenciesData && value && frequenciesData[value]){
                this.value(frequenciesData[value].initial_fee);
            }

            if (this.value() == 0) {
                visible = false;
            }
            this.visible(visible);
        }
    })
});
