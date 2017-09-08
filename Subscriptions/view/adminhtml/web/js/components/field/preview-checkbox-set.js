/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/checkbox-set'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            showPreview: false,
            previewElementTmpl: 'TNW_Subscriptions/form/element/template/preview-label',
            listens: {
                showPreview: 'onShowPreviewChanged'
            }
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super().
            observe('showPreview');

            return this;
        },

        /**
         * Returns preview label.
         *
         * @returns {string}
         */
        getPreviewLabel: function () {
            return this.getPreview();
        },

        /**
         * Resets value if "showPreview" property changed.
         *
         * @param value
         */
        onShowPreviewChanged: function (value) {
            if (value && this.initialValue && this.value() !== this.initialValue){
                this.reset();
            }
        }
    });
});
