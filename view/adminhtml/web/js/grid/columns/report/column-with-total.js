/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/grid/columns/column'
], function (
    Column
) {
    'use strict';

    /**
     * Subscriptions report grid column with totals footer.
     *
     * @class ColumnWithTotal
     */
    var ColumnWithTotal = Column.extend({
        defaults: {
            totalTmpl: 'TNW_Subscriptions/grid/report/column-total'
        },

        /**
         * Returns knockout template for total table footer cell.
         *
         * @returns {string}
         */
        getTotal: function () {
            return this.totalTmpl;
        },

        /**
         * Returns column total for current column index.
         *
         * @param {object} totals
         * @returns {string|number}
         */
        getTotalValue(totals) {
            return totals && totals[this.index];
        }
    });

    return ColumnWithTotal;
});
