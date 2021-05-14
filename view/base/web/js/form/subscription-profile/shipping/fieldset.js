/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'jquery',
    'Magento_Ui/js/lib/spinner',
    'jquery/ui'
], function (Fieldset, registry, $j) {
    'use strict';

    return Fieldset.extend({
        defaults: {
            shipping_errors: null,
            options: []
        },

        /**
         * Calls initObservable of parent class.
         * Defines observable properties of instance.
         *
         * @returns {Object} Reference to instance
         */
        initObservable: function () {
            this._super()
                .observe('shipping_errors');

            return this;
        },

        /**
         * Processing errors
         */
        processErrors: function (errors) {
            this.set('shipping_errors', errors);
        },

        /**
         * Resets shipping errors.
         */
        resetErrors:function () {
            this.set('shipping_errors', '');
        }
    });
});
