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
            periodPreviewLabel: '',
            default: "1",
            visibleOnEdit: true
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super().observe('showPreview periodPreviewLabel');

            return this;
        },

        /**
         * Returns preview label.
         *
         * @returns {string}
         */
        getPreviewLabel: function () {
            var result;
            if (this.checked()) {
                result = this.previewLabel;
            } else {
                result = this.periodPreviewLabel;
            }

            return result;
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
                this.visible(this.visibleOnEdit);
            }
        }
    });
});
