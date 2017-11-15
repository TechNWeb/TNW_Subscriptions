/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'TNW_Subscriptions/js/components/modify-subscriptions-form',
        'uiRegistry'
    ],
    function ($, Component, registry) {
        'use strict';

        return Component.extend({
            /**
             * Process response status.
             */
            processResponseStatus: function () {
                if (this.responseStatus()) {
                    var responseData = this.responseData();
                    if (typeof responseData === 'object'
                        && responseData['objects_count'] !== undefined
                        && responseData['objects_count'] === 0) {
                        window.location.reload();
                    }
                    var localStorage = $.initNamespaceStorage('mage-cache-storage-section-invalidation').localStorage;
                    localStorage.set('tnw-subscriptions-subscription-cart', true);
                    registry.get('cart.steps').renderCurrentStep();
                }
            }
        });
    }
);
