/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-field'
], function (Abstract) {
    'use strict';

    return Abstract.extend({

        /**
         * Checks if it is possible to edit qty field.
         * It depends on mode (preview or edit) and unlock preset qty field from product.
         *
         * @param previewMode
         */
        canShowEdit: function (previewMode) {
            var currentItemData = this.source.data['item_' + this.item_id];
            var unlockPresetQty = currentItemData.unlock_preset_qty;
            var showPreview = true;

            if (!unlockPresetQty) {
                showPreview = false;
                if (previewMode) {
                    showPreview = true;
                }
            }

            this.showPreview(showPreview);
        }
    });
});
