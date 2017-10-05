/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-field',
    'uiRegistry'
], function (Abstract, registry) {
    'use strict';

    return Abstract.extend({
        defaults: {
            parentForm: null
        },

        /**
         * Checks if it is possible to edit qty field.
         * It depends on mode (preview or edit) and unlock preset qty field from product.
         *
         * @param previewMode
         */
        canShowEdit: function (previewMode) {
            var parent = this.getParentForm(),
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

        getParentForm: function() {
            var parent = null;
            if (this.parentForm) {
                parent = registry.get(this.parentForm);
            }

            return parent;
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
