/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'jquery',
    'TNW_Subscriptions/js/formatPrice',
    'mage/translate',
    'jquery/ui'
], function (Abstract, registry, $, formatPrice) {
    'use strict';

    return Abstract.extend({

        /**
         * Sets initial value of the element and subscribes to it's changes.
         */
        setInitialValue: function () {
            this._super();
            this.changeComment();

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.changeComment();
        },

        /**
         * Fires to change comment.
         */
        changeComment: function() {
            //Get current price format
            var priceFormat = null;
            if (typeof this.priceFormat != 'undefined' && this.priceFormat != null) {
                priceFormat = $.parseJSON(this.priceFormat);
            }
            //Get current component integer value.
            var trialPrice = this.value();
            if (typeof trialPrice == 'string') {
                trialPrice = formatPrice.formatToNumber(trialPrice, priceFormat);
            }

            if (trialPrice != 0) {
                var productPriceComponent = registry.get('index=price');
                var productPrice = 0;
                var commentPrice = 0;


                //Find product price.
                if ((typeof productPriceComponent != 'undefined')
                    && (typeof productPriceComponent.value() != 'undefined')) {
                    productPrice = formatPrice.formatToNumber(productPriceComponent.value(), priceFormat);
                }

                //If product price is not 0 we can calculate amount to show in comment.
                if (productPrice != 0) {
                    commentPrice = productPrice - trialPrice;
                }

                //If amount for comment isn't 0 we can calculate comment to show it.
                if (commentPrice != 0) {
                    commentPrice = formatPrice.formatPrice(commentPrice, priceFormat);
                    this.notice = $.mage.__('Estimated');
                    this.notice += ' ' + this.addbefore + commentPrice + ' ';
                    this.notice += $.mage.__('savings to the customer during the trial period.');
                } else {
                    this.notice = ' ';
                }
            } else {
                this.notice = $.mage.__('Product is FREE for the trial period.');
            }

            $('#'+this.noticeId).children().html(this.notice);
        }
    });
});
