define([
    'jquery',
    'TNW_Subscriptions/js/components/add-product-form',
    'uiRegistry'
], function ($, Component, registry) {
    'use strict';

    return Component.extend({
        /**
         * Process response status.
         */
        processResponseStatus: function () {
            var mainModal,
                grid,
                productInsertForm;

            if (this.responseStatus()) {
                mainModal = registry.get('index=' + this.source.mainModal);
                //reset grid
                grid = registry.get('index= '+ this.source.modalGrid);
                grid.destroyInserted();
                //reset form
                this.getModalForm().set('visible', false);
                //close modal
                mainModal.closeModal();
                //reload sub listing
                productInsertForm = registry.get('index=' + this.source.productIndertForm);
                productInsertForm.destroyInserted();
                productInsertForm.render();
            }
        }
    });
});
