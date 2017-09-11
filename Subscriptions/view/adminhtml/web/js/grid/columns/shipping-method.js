/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/grid/columns/column',
    'jquery'
], function (Column, $j) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'TNW_Subscriptions/grid/cells/shipping-method',
            value: null,
            dependsCodes: [],
            attentionMessage: '',
            listens: {
                'value': 'onValueChange'
            }
        },

        /**
         * Initializes observable properties.
         *
         * @returns {Column} Chainable.
         */
        initObservable: function () {
            this._super()
                .observe([
                    'value'
                ]);

            return this;
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
        },

        /**
         * Return attention message.
         *
         * @returns {string}
         */
        getAttentionMessage: function () {
            return this.attentionMessage;
        },

        /**
         * Check if need show attention message block.
         *
         * @param row
         * @returns {exports.needShowAttention}
         */
        needShowAttention: function (row) {
            return row[this.index].needShowAttention;
        },

        /**
         * Select value change.
         * Hide or show attention message.
         *
         * @param value
         */
        onValueChange: function (value) {
            $j(".subscription-shipping-attention").hide();
            if (value) {
                var currentShippingMethod = shippingMethod.split('_');

                this.dependsCodes.forEach(function(item, i) {
                    if (item === currentShippingMethod[0]) {
                        $j(".subscription-shipping-attention").show();
                        return false;
                    }
                });
            }
        }
    });
});
