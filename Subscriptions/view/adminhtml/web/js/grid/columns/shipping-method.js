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
            bodyTmpl: 'TNW_Subscriptions/grid/cells/shipping-method'
        },
        hasOptions: function (row) {
            return row[this.index].methods.length > 0;
        },
        hasLabel: function (row) {
            return row[this.index].label !== '';
        },
        getOptions: function (row) {
            return row[this.index].methods;
        },
        getLabel: function (row) {
            return row[this.index].label;
        },
        getForm: function () {
            return this.saveForm;
        },
        getId: function (row) {
            return row[this.index].sub_quote_id;
        }
    });
});
