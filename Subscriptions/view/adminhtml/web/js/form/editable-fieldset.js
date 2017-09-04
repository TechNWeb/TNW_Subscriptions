/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'jquery',
    'underscore'
], function (Fieldset, registry, $j, _) {
    'use strict';

    return Fieldset.extend({
        defaults: {
            preview: true
        },

        /**
         * Calls initObservable of parent class.
         * Defines observable properties of instance.
         *
         * @returns {Object} Reference to instance
         */
        initObservable: function () {
            this._super()
                .observe('preview');

            return this;
        },

        /**
         * Enables preview mode
         *
         * @returns {Object} Reference to instance
         */
        enablePreview: function () {
            this.set('preview', true);
            return this;
        },

        /**
         * Disables preview mode
         *
         * @returns {Object} Reference to instance
         */
        disablePreview: function () {
            this.set('preview', false);
            return this;
        },

        /**
         * Save data from this fieldset and reload it
         */
        saveAndUpdate: function () {

        }
    });
});
