/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
            visibleOnEdit: false,
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
            if (Date.parse(this.current_date) === Date.parse(result)){
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
            var result = Date.parse(this.shiftedValue()),
                currentDate = Date.parse(this.current_date);
            if (value){
                this.visible(true);
                if (this.initialValue && this.value() !== this.initialValue){
                    this.reset();
                }
            } else {
                if (currentDate >= result) {
                    this.showPreview(true);
                    this.visibleOnEdit = true;
                }
                this.visibleOnEdit ? this.visible(true) : this.visible(false);
            }
        }
    });
});
