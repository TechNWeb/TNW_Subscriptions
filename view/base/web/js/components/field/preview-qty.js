/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-field'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            listens: {
                '${ $.parentFormName }.edit_fieldset.billing_frequency:value': 'changeBillingFrequency'
            },
            imports: {
                'canShowEdit': '${ $.parentFormName }:previewMode'
            },
            exports: {
                'value': '${ $.parentFormName }.edit_fieldset.billing_frequency:changeItemPriceLabel'
            },
            modules: {
                parentForm: '${ $.parentFormName }'
            }
        },

        changeBillingFrequency: function(value) {
            var form = this.parentForm();
            if (form && value) {
                var frequenciesData = form.source.data['item_' + form.additionalData.objectItemId]
                        .frequency_data.product_frequencies,
                    unlockPresetQty = !!parseInt(form.source.data['item_' + form.additionalData.objectItemId]
                        .unlock_preset_qty);

                if (unlockPresetQty && frequenciesData && value && frequenciesData[value]){
                    this.value(frequenciesData[value].preset_qty);
                }
            }
        },

        /**
         * Checks if it is possible to edit qty field.
         * It depends on mode (preview or edit) and unlock preset qty field from product.
         *
         * @param previewMode
         */
        canShowEdit: function (previewMode) {
            var parent = this.parentForm(),
                currentItemData = null,
                unlockPresetQty = 0,
                showPreview = true;

            if (parent) {
                currentItemData = parent.source.data['item_' + parent.additionalData.objectItemId];
                unlockPresetQty = currentItemData.unlock_preset_qty;
            }

            if (!unlockPresetQty) {
                showPreview = false;
                if (previewMode) {
                    showPreview = true;
                }
            }

            this.showPreview(showPreview);
        },

        /**
         * Checks if element has addons
         *
         * @returns {Boolean}
         */
        hasAddons: function () {
            return this.addbefore || this.addafter;
        }
    });
});
