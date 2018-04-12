/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'underscore',
        'ko',
        'uiComponent',
        'TNW_Subscriptions/js/ui/model/step-navigator',
        'uiRegistry',
        'jquery/jquery.hashchange'
    ],
    function ($, _, ko, Component, stepNavigator, registry) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'TNW_Subscriptions/cart/progress-bar',
                steps: []
            },

            /**
             * @inheritdoc
             */
            initialize: function () {
                this._super()
                    .navigateAccordingToUrl()
                    .initSteps();

                $(window).hashchange(_.bind(stepNavigator.handleHash, stepNavigator));
                stepNavigator.handleHash();
            },

            /**
             * Init steps
             *
             * @returns {exports}
             */
            initSteps: function () {
                $.each(this.steps, function () {
                    stepNavigator.registerStep(this);
                });

                return this;
            },

            /**
             * Init steps
             *
             * @returns {exports}
             */
            navigateAccordingToUrl: function () {
                var step = window.location.hash.substr(1);
                if (step) {
                    var isLoggedIn = registry.get('cart').checkoutConfig.isCustomerLoggedIn;
                    var isVirtual = registry.get('cart').checkoutConfig.isSubscriptionsVirtual;

                    if (step === 'registration' && isLoggedIn) {
                        step = 'shipping';
                        if (isVirtual) {
                            step = 'billing';
                        }
                        stepNavigator.applyHash(step);
                    } else if (step === 'shipping' && isVirtual) {
                        step = 'billing';
                        stepNavigator.applyHash(step);
                    }

                    $.each(this.steps, function () {
                        this.code === step ? this.isActive = true : this.isActive = false;
                    });
                }

                return this;
            },

            /**
             * Get steps observable
             *
             * @returns {Function}
             */
            getSteps: function () {
                return stepNavigator.steps;
            },

            /**
             * Sort items
             *
             * @param {Object} itemOne
             * @param {Object} itemTwo
             * @returns {*|number}
             */
            sortItems: function (itemOne, itemTwo) {
                return stepNavigator.sortItems(itemOne, itemTwo);
            },

            /**
             * Navigate to step
             *
             * @param {Object} step
             */
            navigateTo: function (step) {
                stepNavigator.navigateBackTo(step.code);
            },

            /**
             * Check if item is processed
             *
             * @param {Object} item
             * @returns {*|boolean}
             */
            isProcessed: function (item) {
                return stepNavigator.isProcessed(item.code);
            }
        });
    }
);
