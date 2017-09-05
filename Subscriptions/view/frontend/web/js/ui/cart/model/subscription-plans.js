/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
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
            },

            /**
             * Get item by Id
             *
             * @param {number} planId
             * @returns {Object|undefined}
             */
            getItemById: function (planId) {
                var item;

                $.each(this.getItems(), function () {
                    if (this.subscription_plan_id == planId) {
                        item = this;
                    }
                });

                return item;
            }
        };
    }
);
