/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'mage/translate'
], function (Abstract, $j) {
    'use strict';

    return Abstract.extend({
        defaults: {
            showPreview: true,
            previewLabel: '',
            previewLabelVisible: true,
            completePreviewLabel: '',
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
            observe('showPreview completePreviewLabel');

            return this;
        },

        /**
         * Sets initial value of the element and subscribes to it's changes.
         */
        setInitialValue: function () {
            this._super();
            this.setCompletePreviewLabel(this.value());

            return this;
        },

        /**
         * Returns preview label.
         *
         * @returns {boolean, string}
         */
        getPreviewLabel: function () {
            return this.previewLabelVisible ? this.completePreviewLabel() : false;
        },

        /**
         * Sets complete preview label.
         *
         * @param value
         */
        setCompletePreviewLabel: function (value) {
            this.completePreviewLabel(
                $j.mage.__(this.previewLabel).replace('%s', value)
            );
        },

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            this.setCompletePreviewLabel(value);
            return this._super();
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
