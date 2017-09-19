/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-checkbox'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            periodPreviewLabel: '',
            default: "1"
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super().
            observe('showPreview periodPreviewLabel');

            return this;
        },

        /**
         * Returns preview label.
         *
         * @returns {string}
         */
        getPreviewLabel: function () {
            var result;
            if (this.checked()){
                result = this.previewLabel;
            }else {
                result = this.periodPreviewLabel;
            }

            return result;
        }
    });
});
