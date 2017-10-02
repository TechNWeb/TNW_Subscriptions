/**
 * Copyright 2016 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'mage/translate',
        'Magento_Ui/js/form/element/date',
        'TNW_Subscriptions/js/ui/cart/model/subscription-plans'
    ],
    function ($,
              ko,
              Component,
              $t,
              $dateEl,
              subscriptionPlans) {
        'use strict';

        var items;

        /**
         * Init items observable
         *
         * @param {Array} items
         * @returns {Function}
         */
        function initItems(items) {
            var observableItems = [];
            $.each(items, function () {
                var products = [];
                $.each(this.products, function () {
                   products.push({
                       'name': ko.observable(this.name),
                       'description': ko.observable(this.description),
                       'price': ko.observable(this.price),
                       'term': ko.observable(this.term),
                       'image': ko.observable(this.image),
                       'qty': ko.observable(this.qty),
                       'period': ko.observable(this.period),
                       'start_on': ko.observable(this.start_on),
                       'billing_frequencies' : this.billing_frequencies
                   });
                });
                var plan = {
                    'plan_title': this.plan_title,
                    'plan_id': this.quote_id,
                    'products': products
                };
                observableItems.push(plan);
            });
            return ko.observableArray(observableItems);
        }

        return Component.extend({
            items: {},

            /**
             * @inheritdoc
             */
            initialize: function () {
                this._super()
                    ._initItems();
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
            }
        });
    }
);
