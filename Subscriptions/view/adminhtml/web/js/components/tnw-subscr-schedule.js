/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/select',
    'jquery'
], function (Select, $) {
    'use strict';

    return Select.extend({
        defaults: {
            selectDefinedByCustomer: false
        },

        initialize: function () {
            this._super()
                .observe('selectDefinedByCustomer')
                .changeValue();

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.changeValue();
        },

        /**
         * Fires to change comment.
         */
        changeValue: function() {
            var value = this.value();

            this.selectDefinedByCustomer(value == 2);
        }
    });
});
