/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/edit-button',
    'uiRegistry',
], function (Button, registry) {
    'use strict';

    return Button.extend({
        defaults: {
            elementTmpl: 'TNW_Subscriptions/form/element/edit-button',
            editOptionsModal: null
        },

        /**
         * 'Edit options' button click action.
         *
         * @param {string|number} productId
         * @param {string|number} itemId
         * @param {string|number} quoteId
         */
        editOptions: function (productId, itemId, quoteId) {
            this.openConfigurableModal(productId, itemId, quoteId);
        },

        /**
         * Opens Configure product options modal window and renders form
         *
         * @param {string|number} productId
         * @param {string|number} itemId
         * @param {string|number} quoteId
         */
        openConfigurableModal: function (productId, itemId, quoteId) {
            var editOptionsForm = registry.get('index=' + this.source.insertEditOptionsForm),
                params = {
                    'product_id': productId,
                    'item_id': itemId,
                    'quote_id': quoteId
                };
            this.getConfigureModal().openModal();
            this.renderForm(editOptionsForm, params);
        },

        /**
         * Finds and returns in uiRegistry Configure options modal window
         */
        getConfigureModal: function () {
            if (!this.editOptionsModal) {
                this.editOptionsModal = registry.get('index=' + this.source.editOptionsModal);
            }

            return this.editOptionsModal;
        },

        /**
         * Render form data.
         *
         * @param {Object} form
         * @param {Object} params
         */
        renderForm: function (form, params) {
            form.set('visible', true);
            form.destroyInserted();
            form.render(params);
        }
    });
});
