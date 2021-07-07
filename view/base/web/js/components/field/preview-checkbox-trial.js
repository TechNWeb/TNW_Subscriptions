/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-checkbox'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            visibleOnEdit: true,
            previewLabelYes: 'Yes',
            previewLabelNo: 'No'
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super().observe('showPreview');

            return this;
        },

        /**
         * Returns preview label.
         *
         * @returns {string}
         */
        getPreviewLabel: function () {
            return this.value() ? this.previewLabelYes : this.previewLabelNo;
        },

        /**
         * Resets value if "showPreview" property changed.
         *
         * @param {bool} value
         */
        onShowPreviewChanged: function (value) {
            if (value) {
                this.visible(true);
                if (this.initialValue && this.value() !== this.initialValue) {
                    this.reset();
                }
            } else {
                if (this.disabled()) {
                    this.showPreview(true);
                }
                this.visible(this.visibleOnEdit);
            }
        }
    });
});
