/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/grid/listing'
], function (
    Listing
) {
    'use strict';

    /**
     * Subscriptions report grid listing.
     *
     * @class ReportSubscriptions
     */
    var ReportSubscriptions = Listing.extend({
        defaults: {
            template: 'TNW_Subscriptions/grid/report-subscriptions',
            imports: {
                rows: '${ $.provider }:data.items',
                totals: '${ $.provider }:data.totals'
            },
        },

        /**
         * Initializes observable properties.
         *
         * @returns {ReportSubscriptions} Chainable.
         */
        initObservable: function () {
            this._super()
                .track({
                    totals: null
                });

            return this;
        },

        /**
         * Checks if grid has totals.
         *
         * @returns {boolean}
         */
        hasTotals: function () {
            return !!this.totals;
        }
    });

    return ReportSubscriptions;
});
