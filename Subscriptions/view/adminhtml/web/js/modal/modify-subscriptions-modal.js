/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/modal/modal-component',
    'uiRegistry'
], function (ModalComponent, uiRegistry) {
    'use strict';

    return ModalComponent.extend({
        defaults: {
            wasModified: false
        },

        /**
         * Initializes observable properties.
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super();
            this.observe(['wasModified']);

            return this;
        },

        /**
         * Wrap content in a modal of certain type
         *
         * @param {Boolean} state
         */
        onState: function (state) {
            this._super();
            if (!state && this.wasModified()){
                var grid = uiRegistry.get('index  = tnw_subscriptionprofile_product_columns');
                grid.source.set('params.t ', Date.now());
                this.wasModified(false);
            }
        }
    });
});
