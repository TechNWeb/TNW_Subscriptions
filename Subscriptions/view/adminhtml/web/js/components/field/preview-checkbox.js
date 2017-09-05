/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/single-checkbox'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            showPreview: false,
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
         * @inheritdoc
         */
        setInitialValue: function () {
            if (_.isEmpty(this.valueMap)) {
                this.on('value', this.onUpdate.bind(this));
            } else {
                this._super();
                this.checked(this.getReverseValueMap(this.value()));
            }

            return this;
        },

        /**
         * Returns preview label.
         *
         * @returns {string}
         */
        getPreviewLabel: function () {
            return this.previewLabel;
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
