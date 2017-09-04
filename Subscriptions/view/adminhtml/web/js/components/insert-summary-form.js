define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        'uiRegistry',
        'underscore'
    ],
    function ($, Component, registry, _) {
        'use strict';

        return Component.extend({
            defaults: {
                ajaxSave: true,
                listens: {
                    responseStatus: 'processResponseStatus'
                }
            },

            /**
             * Process response status.
             */
            processResponseStatus: function () {
                var shippingInsertFrom;

                if (this.responseStatus()) {
                    shippingInsertFrom = registry.get('index=' + this.source.shippingInsertForm);
                    shippingInsertFrom.destroyInserted();
                    shippingInsertFrom.render();
                }
            },

            /**
             * Render form data.
             */
            renderForm: function (form, params) {
                form.set('visible', true);
                form.destroyInserted();
                form.render(params);
            }
        });
    }
);
