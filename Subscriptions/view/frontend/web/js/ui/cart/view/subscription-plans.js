/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'mage/translate',
        'TNW_Subscriptions/js/ui/cart/model/cart',
        'TNW_Subscriptions/js/ui/cart/model/subscription-plans',
        'TNW_Subscriptions/js/ui/cart/action/select-subscription-plan',
        'TNW_Subscriptions/js/ui/model/step-navigator'
    ],
    function(
        $,
        ko,
        Component,
        $t,
        cart,
        subscriptionPlans,
        selectSubscriptionPlanAction,
        stepNavigator
    ) {
        'use strict';

        var selectBtnTitle,
            selectBtnTitleSelected,
            selectBtnTitleUnselect,
            items;

        /**
         * Init items observable
         *
         * @param {Array} items
         * @returns {Function}
         */
        function initItems (items) {
            var observableItems = [];

            $.each(items, function () {
                this.isSelected = ko.observable(false);
                this.selectButtonTitle = ko.observable(selectBtnTitle);
                observableItems.push(this);
            });

            return ko.observableArray(observableItems);
        }

        /**
         * Mark items isSelected property according to plan Id
         *
         * @param {number} planId
         */
        function markItemSelected (planId) {
            var itemsToUpdate = items();

            $.each(itemsToUpdate, function () {
                var isSelected = this.subscription_plan_id == planId,
                    buttonTitle = isSelected ? selectBtnTitleSelected : selectBtnTitle;

                this.isSelected(isSelected);
                this.selectButtonTitle(buttonTitle);
            });

            items(itemsToUpdate);
        }

        return Component.extend({
            items: {},
            isPlanSelected: cart.isPlanSelected,

            defaults: {
                selectBtnTitle: 'Select Plan',
                selectBtnTitleSelected: 'Selected',
                selectBtnTitleUnselect: 'Unselect'
            },

            /**
             * @inheritdoc
             */
            initialize: function() {
                this._super()
                    ._initSelectBtnTitles()
                    ._initItems();
            },

            /**
             * Initialize select button titles
             *
             * @returns {Class}
             * @private
             */
            _initSelectBtnTitles: function () {
                selectBtnTitle = this.selectBtnTitle;
                selectBtnTitleSelected = this.selectBtnTitleSelected;
                selectBtnTitleUnselect = this.selectBtnTitleUnselect;

                return this;
            },

            /**
             * Init subscription plan items observables
             *
             * @returns {Class}
             * @private
             */
            _initItems: function () {
                items = initItems(subscriptionPlans.getItems());
                this.items = items;

                return this;
            },

            /**
             * On select plan click event handler
             *
             * @param {Object} plan
             */
            onSelectPlanClick: function (plan) {
                var selectedPlanId = cart.getSubscriptionPlanId(),
                    planId = plan.subscription_plan_id,
                    isResetCurrent = selectedPlanId == planId;

                if (isResetCurrent) {
                    planId = null;
                }
                selectSubscriptionPlanAction(planId).done(function () {
                    if (!selectedPlanId) {
                        stepNavigator.navigateNext();
                    }
                    if (isResetCurrent) {
                        stepNavigator.navigateBackTo('products');
                    }
                    markItemSelected(planId);
                });
            },

            /**
             * On select plan mouseover event handler
             *
             * @param {Object} plan
             */
            onSelectPlanMouseOver: function (plan) {
                if (plan.isSelected()) {
                    plan.selectButtonTitle(selectBtnTitleUnselect);
                }
            },

            /**
             * On select plan mouseout event handler
             *
             * @param {Object} plan
             */
            onSelectPlanMouseOut: function (plan) {
                if (plan.isSelected()) {
                    plan.selectButtonTitle(selectBtnTitleSelected);
                }
            }
        });
    }
);
