/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'ko',
        'TNW_Subscriptions/js/ui/cart/model/cart',
        'TNW_Subscriptions/js/ui/cart/model/subscription-plans'
    ],
    function(ko, cart, subscriptionPlans) {
        'use strict';

        /**
         * Init observable for plan entity field
         *
         * @param {String} fieldName
         */
        function initFieldObservable(fieldName) {
            var initValue = plan() ? plan()[fieldName] : null,
                observable = ko.observable(initValue);

            plan.subscribe(function (planData) {
                if (planData) {
                    observable(planData[fieldName]);
                }
            });

            return observable;
        }

        var plan = ko.observable(subscriptionPlans.getItemById(cart.getSubscriptionPlanId())),
            numberOfPayments = initFieldObservable('number_of_payments'),
            repeat = initFieldObservable('repeat'),
            isTrialPeriodEnabled = initFieldObservable('is_trial_period_enabled'),
            trialPeriod = initFieldObservable('trial_total_billing_cycles'),
            startDateType = initFieldObservable('start_date_type'),
            start = initFieldObservable('start'),
            subtotal = 33, //ko.observable(cart.cartData().subtotal),
            trialSubtotal = 33;//ko.observable(cart.cartData().trial_subtotal),
            //startDate = ko.observable(cart.startDate());

        /*cart.planId.subscribe(function (planId) {
            plan(subscriptionPlans.getItemById(planId));
        });
        cart.cartData.subscribe(function (cartData) {
            subtotal(cartData.subtotal);
            trialSubtotal(cartData.trial_subtotal);
        });*/

        return {
            /**
             * Get number of payments observable
             *
             * @returns {Function}
             */
            getNumberOfPayments: function () {
                return numberOfPayments;
            },

            /**
             * Get repeat observable
             *
             * @returns {Function}
             */
            getRepeat: function () {
                return repeat;
            },

            /**
             * Get is trial period enabled observable
             *
             * @returns {Function}
             */
            getIsTrialPeriodEnabled: function () {
                return isTrialPeriodEnabled;
            },

            /**
             * Get trial period observable
             *
             * @returns {Function}
             */
            getTrialPeriod: function () {
                return trialPeriod;
            },

            /**
             * Get start field value observable
             *
             * @returns {Function}
             */
            getStart: function () {
                return start;
            },

            /**
             * Get start date observable
             *
             * @returns {Function}
             */
            getStartDateType: function () {
                return startDateType;
            },

            /**
             * Get start date observable
             *
             * @returns {Function}
             */
            getStartDate: function () {
                return '';//startDate;
            },

            /**
             * Get subtotal observable
             *
             * @returns {Function}
             */
            getSubtotal: function () {
                return subtotal;
            },

            /**
             * Get trial subtotal observable
             *
             * @returns {Function}
             */
            getTrialSubtotal: function () {
                return trialSubtotal;
            }
        };
    }
);
