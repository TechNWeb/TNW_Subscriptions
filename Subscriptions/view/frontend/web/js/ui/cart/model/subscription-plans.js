/**
 * Copyright 2016 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

define(
    ['jquery'],
    function($) {
        'use strict';

        return {
            /**
             * Get subscription plans
             *
             * @returns {Array}
             */
            getItems: function () {
                return window.tnwSubscriptionsCheckoutConfig.subscriptionPlans;
            }
        };
    }
);
