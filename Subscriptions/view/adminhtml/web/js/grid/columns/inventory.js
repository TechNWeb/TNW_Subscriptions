/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/grid/columns/column',
    'jquery',
    'mage/translate'
], function (Column, $) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'TNW_Subscriptions/grid/cells/cell-with-warning',
            defaultQty: 1,
            itemMessages: {},
            inputQties: {}
        },

        /**
         * Returns tooltip text to show in grid.
         *
         * @param {} row
         * @returns {string}
         */
        getToolTipText: function (row) {
            var result = '';

            if (this.getItemMessage(row)) {
                result = this.getItemMessage(row) + ' ' + this.tooltip.description;
            }

            return result;
        },

        /**
         * Returns column class (depends on the ability to buy goods).
         *
         * @param {} row
         * @returns {string}
         */
        updateClass: function (row) {
            var result = 'in-stock';

            if (this.getItemMessage(row)) {
                result = 'warning-not-in-stock';
            }

            return result;
        },

        /**
         * Returns error message if there are not enough products.
         *
         * @param {} row
         * @returns {string}
         */
        getItemMessage: function (row) {
            var qtyChanged = true,
                currentQty;
            if (this.inputQties[row.entity_id]) {
                if (row.input_qty) {
                    if ((row.input_qty * 1) == this.inputQties[row.entity_id]) {
                        qtyChanged = false;
                    }
                    currentQty = row.input_qty * 1;
                } else if (this.inputQties[row.entity_id] == this.defaultQty) {
                    qtyChanged = false;
                    currentQty = this.defaultQty
                } else {
                    currentQty = this.defaultQty;
                }
            } else {
                currentQty = this.defaultQty;

                if (row.input_qty) {
                    currentQty = row.input_qty * 1;
                }
            }

            this.inputQties[row.entity_id] = currentQty;

            if (qtyChanged) {
                this.itemMessages[row.entity_id] = this.resolveInputQty(row);
            }

            return this.itemMessages[row.entity_id];
        },

        /**
         * Checks if the product can be bought.
         *
         * @param {} row
         * @returns {string}
         */
        resolveInputQty: function (row) {
            var message = '',
                productId = row.entity_id,
                productStockData = this.stockData[productId],
                currentQty = this.defaultQty,
                productType = row.type_id;

            if (typeof productType != 'undefined' && productType != 'configurable') {
                if (typeof row.input_qty != 'undefined' && row.input_qty) {
                    currentQty = row.input_qty * 1;
                }

                if (productStockData) {
                    if (productStockData.min_sale_qty && currentQty < productStockData.min_sale_qty) {
                        message = $.mage.__(this.warningMessages['not_min_sale'])
                            .replace('%1', productStockData.min_sale_qty);

                        return message;
                    }

                    if (productStockData.max_sale_qty && currentQty > productStockData.max_sale_qty) {
                        message = $.mage.__(this.warningMessages['not_max_sale'])
                            .replace('%1', productStockData.max_sale_qty);

                        return message;
                    }

                    if (!productStockData.manage_stock) {
                        return message;
                    }

                    if (!productStockData.is_in_stock) {
                        message = $.mage.__(this.warningMessages['not_in_stock']);

                        return message;
                    }

                    if ((productStockData.qty - productStockData.min_qty - currentQty < 0) && !productStockData.backorders) {
                        message = $.mage.__(this.warningMessages['too_much'])
                            .replace('%1', row.name);

                        return message;
                    }
                }
            }

            return message;
        },

        /** @inheritDoc */
        getLabel: function (record) {
            if (record.type_id == 'configurable') {
                return '';
            }

            return record[this.index];
        }
    });
});
