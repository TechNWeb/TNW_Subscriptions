/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
            var priceComponent = registry.get('index=price');
            var productPrice = 0;
            var commentPrice = 0;
            var trialPrice = this.value() * 1;

            //Find product price.
            if ((typeof priceComponent != 'undefined')
                && (typeof priceComponent.value() != 'undefined')) {
                productPrice = priceComponent.value() * 1;
            }

            //If current component value and product price are not 0 we can calculate amount to show in comment.
            if (trialPrice != 0 && productPrice != 0) {
                commentPrice = productPrice - trialPrice;
            }

            //If amount for comment isn't 0 we can calculate comment ro show it.
            if (commentPrice != 0) {
                commentPrice = formatPrice.formatPrice(commentPrice);
                this.notice = $.mage.__('Estimated');
                this.notice += ' ' + this.addbefore + commentPrice + ' ';
                this.notice += $.mage.__('savings to the end consumer during the trial period.');
            } else {
                this.notice = ' ';
            }

            $('#'+this.noticeId).html(this.notice);
        }
    });
});
