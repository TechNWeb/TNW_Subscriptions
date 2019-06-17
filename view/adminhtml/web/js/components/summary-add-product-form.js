define([
    'jquery',
    'TNW_Subscriptions/js/components/add-product-form',
    'uiRegistry'
], function ($, Component, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            listens: {
                responseData: 'processResponseData'
            }
        },

        /**
         * Process response data.
         */
        processResponseData: function(data) {
            var mainModal,
                grid,
                productInsertForm;

            mainModal = registry.get('index=' + this.source.mainModal);
            //reset grid
            grid = registry.get('index= '+ this.source.modalGrid);
            grid.destroyInserted();
            //reset form
            this.getModalForm().set('visible', false);
            //close modal
            mainModal.closeModal();

            if (data.error) {
                registry.get(this.source.profileErrorAria)
                    .cleanMessageData()
                    .initMessagesData([data.messages]);

                return;
            }

            //reload sub listing
            productInsertForm = registry.get('index=' + this.source.productIndertForm);
            productInsertForm.destroyInserted();
            productInsertForm.render();
        },

        /**
         * Process response status.
         */
        processResponseStatus: function () {
            // Empty
        }
    });
});
