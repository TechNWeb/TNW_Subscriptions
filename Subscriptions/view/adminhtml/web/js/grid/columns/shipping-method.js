/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/grid/columns/column',
    'jquery',
    'underscore'
], function (Column, $j, _) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'TNW_Subscriptions/grid/cells/shipping-method',
            value: null,
            dependsCodes: [],
            attentionMessage: '',
            /*listens: {
                'value': 'onValueChange'
            }*/
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
            _.bindAll(this, 'onValueChange');

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
         * @param id
         */
        onValueChange: function (id) {
            var messageElem = $j("#subscription-shipping-attention-" + id).get(0);
            if (messageElem) {
                $j(messageElem).hide();
            } else {
                $j(".subscription-shipping-attention").hide();
            }
            var element = $j("select[name='shipping_methods[" + id + "]']").get(0);
            if (element) {
                var currentSelectedValue = element.value;
                if (currentSelectedValue) {
                    var currentShippingMethod = currentSelectedValue.split('_');
                    if (this.dependsCodes.indexOf(currentShippingMethod[0]) === -1) {
                        $j("#subscription-shipping-attention-" + id).show();
                    }
                }
            }
        }
    });
});
