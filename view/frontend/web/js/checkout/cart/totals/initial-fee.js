define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/totals',
], function (Component, totals) {
        'use strict';
        return Component.extend({

            isDisplayed: function () {
                return this.getPureValue() ? this.getPureValue() : false;
            },

            getPureValue: function () {
                if (!this.getTotals()) {
                    return null;
                }
                return totals.getSegment('subs_initial_fee').value;
            },

            getValue: function () {
                return this.getFormattedPrice(this.getPureValue());
            }
        });
    }
);
