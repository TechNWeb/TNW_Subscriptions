define(
    [
        'jquery',
        'TNW_Subscriptions/js/components/modify-subscriptions-form',
        'uiRegistry',
        'underscore',
        'TNW_Subscriptions/js/ui/cart/model/steps'
    ],
    function ($, Component, registry, _, steps) {
        'use strict';

        return Component.extend({
            /**
             * Process response status.
             */
            processResponseStatus: function () {
                if (this.responseStatus()) {
                    registry.get('cart.steps').renderCurrentStep();
                }
            }
        });
    }
);
