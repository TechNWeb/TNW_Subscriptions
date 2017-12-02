/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/grid/columns/column',
    'jquery'
], function (Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'TNW_Subscriptions/grid/cells/sub-products'
        },
        getSrc: function (product) {
            return product['thumbnail_src'];
        },
        getAlt: function (product) {
            return product['thumbnail_alt'];
        },
        getProducts: function (row) {
            return row[this.index];
        },
        getConfOptions: function (product) {
            return product['conf_options'] ? product['conf_options'] : [];
        },
        showDottedBorder: function (row, product) {
            var products,
                lastProduct;

            products = row[this.index];
            lastProduct = products.length ? products[products.length - 1] : null;
            return lastProduct && lastProduct.id !== product.id;
        }
    });
});
