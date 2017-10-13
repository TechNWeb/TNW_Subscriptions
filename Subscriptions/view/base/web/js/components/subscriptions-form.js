define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        'uiRegistry',
        'Magento_Ui/js/lib/spinner'
    ],
    function ($, Component, registry, loader) {
        'use strict';

        return Component.extend({
            defaults: {
                useParentLoader: false,
                parentForLoader: ''
            },

            /**
             * Hide loader for this form or for parent element.
             *
             * @returns {Object}
             */
            hideLoader: function () {
                if (this.useParentLoader){
                    registry.get(this.parentForLoader).isLoading(false);
                }else {
                    loader.get(this.name).hide();
                }

                return this;
            }
        });
    }
);
