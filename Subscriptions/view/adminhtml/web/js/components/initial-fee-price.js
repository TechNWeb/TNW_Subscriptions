/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'TNW_Subscriptions/js/formatPrice',
    'jquery'
], function (Abstract, formatPrice, $) {
    'use strict';

    return Abstract.extend({

        /**
         * Sets initial value of the element and subscribes to it's changes.
         */
        setInitialValue: function () {
            this._super();
            this.changeInitialFeeValueFormat();

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.changeInitialFeeValueFormat();
        },

        /**
         * Fires to change comment.
         */
        changeInitialFeeValueFormat: function() {
            //Get current price format
            var priceFormat = this.getPriceFormat(),
                currentValue = this.value();
            priceFormat.pattern = '%s';
            if (typeof currentValue === 'string') {
                currentValue = formatPrice.formatToNumber(currentValue, priceFormat);
            }
            this.value(formatPrice.formatPrice(currentValue, priceFormat));
        },

        /**
         * Return current price format.
         *
         * @returns {*}
         */
        getPriceFormat: function() {
            var priceFormat = null;
            if (typeof this.priceFormat !== 'undefined' && this.priceFormat != null) {
                priceFormat = $.parseJSON(this.priceFormat);
            }

            return priceFormat;
        }
    });
});
