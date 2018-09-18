/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'TNW_Subscriptions/js/checkout/model/totals'
], function (ko, Component, totals) {
    'use strict';

    return Component.extend({
        items: ko.observable([]),

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            // Set initial items to observable field
            this.items(totals.getItems());
            // Subscribe for items data changes and refresh items in view
            totals.getItems().subscribe(function (items) {
                this.items(items);
            }.bind(this));
        }
    });
});
