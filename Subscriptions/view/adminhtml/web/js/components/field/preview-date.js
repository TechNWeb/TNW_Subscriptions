/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/date',
    'jquery',
    'mage/translate'
], function (Abstract, $j) {
    'use strict';

    return Abstract.extend({
        defaults: {
            showPreview: false,
            current_date: null,
            previewLabel: '',
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
            var result = this.shiftedValue();
            if (this.current_date ===  this.shiftedValue()){
                result = $j.mage.__('Today');
            }
            return result;
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
