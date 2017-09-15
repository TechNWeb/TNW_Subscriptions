/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry'
], function (Abstract, registry) {
    'use strict';

    return Abstract.extend({
        defaults: {
            parentForm: null
        },

        /**
         * Fires when Billing Frequency is changed.
         *
         * @param value
         */
        changeValue: function (value) {
            var frequenciesData = this.source.data.product_frequencies,
                visible = true,
                parent = null;

            if (this.modifySubscription && this.getParentForm()) {
                parent = this.getParentForm();
                if (parent) {
                    frequenciesData =
                        parent.source.data['item_' + parent.objectItemId].frequency_data.product_frequencies;
                }
            }

            if (frequenciesData && value && frequenciesData[value]){
                this.value(frequenciesData[value].initial_fee);
            }

            if (this.value() == 0) {
                visible = false;
            }
            this.visible(visible);
        },

        getParentForm: function() {
            var parent = null;
            if (this.parentForm) {
                parent = registry.get(this.parentForm);
            }

            return parent;
        }
    })
});
