/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'TNW_Subscriptions/js/grid/columns/report/column-with-total'
], function (
    ColumnWithTotal
) {
    'use strict';

    /**
     * Subscriptions report grid column with growth indication.
     *
     * @class ColumnWithGrowth
     */
    var ColumnWithGrowth = ColumnWithTotal.extend({
        defaults: {
            bodyTmpl: 'TNW_Subscriptions/grid/cells/report/cell-with-growth',
            disableArrowsColumns: [
                'total_canceled_amount',
                'total_refunded_amount',
                'total_tax_amount',
                'total_tax_amount_actual',
                'total_shipping_amount',
                'total_shipping_amount_actual'
            ]
        },

        /**
         * Checks if growth data is specified.
         *
         * @param {object} row
         * @returns {boolean}
         */
        hasGrowth: function (row) {
            var growthField = this.index + '_growth';
            return (growthField in row) && row[growthField] !== null;
        },

        /**
         * Return growth value for current column.
         *
         * @param {object} row
         * @returns {number}
         */
        getGrowthValue: function (row) {
            var growthField = this.index + '_growth';
            return parseFloat(row[growthField]);
        },

        /**
         * Returns rounded growth value in percents.
         *
         * @param {object} row
         * @returns {string}
         */
        getGrowthPercent: function (row) {
            return Math.round(this.getGrowthValue(row) * 100) + '%';
        },

        /**
         * Returns growth indicator wrapper css class.
         *
         * @param {object} row
         * @returns {string}
         */
        getGrowthCssClass: function (row) {
            var growth = this.getGrowthValue(row);
            var addClass;
            if (!growth) {
                addClass = ' no-growth';
            } else if (growth < 0) {
                addClass = ' growth-negative';
            } else {
                addClass = ' growth-positive';
            }
            return 'growth' + addClass;
        },

        /**
         * Checks if arrow should be displayed in growth indicator.
         *
         * @param {object} row
         * @returns {boolean}
         */
        shouldDisplayGrowthArrow: function (row) {
            return !!this.getGrowthValue(row) && !this.disableArrowsColumns.includes(this.index);
        },

        /**
         * Returns growth indicator arrow element css class.
         *
         * @param {object} row
         * @returns {string}
         */
        getGrowthArrowCssClass: function (row) {
            var growth = this.getGrowthValue(row);
            var suffix = growth < 0 ? 'down' : 'up';
            return 'growth-arrow arrow-' + suffix;
        }
    });

    return ColumnWithGrowth;
});
