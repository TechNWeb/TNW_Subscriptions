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
                },
                insertFormName: null
            },

            /**
             * Process response status.
             */
            processResponseStatus: function () {
                var insertFrom;

                if (this.responseStatus()) {
                    insertFrom = registry.get('index=' + this.insertFormName);
                    insertFrom.destroyInserted();
                    insertFrom.render();
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
