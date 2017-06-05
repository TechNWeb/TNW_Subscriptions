/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'jquery',
    'TNW_Subscriptions/js/formatPrice',
    'mage/translate',
    'jquery/ui'
], function (Abstract, registry, $, formatPrice) {
    'use strict';

    return Abstract.extend({
        defaults: {
            discountAmount: 0
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
            var notice = '';

            //calculate discount amount from the field tnw_subscr_discount_amount
            var discountAmountComponent = registry.get('index=tnw_subscr_discount_amount');
            this.discountAmount = discountAmountComponent.value();

            //if discountAmount field is empty we consider it as 0
            if (this.discountAmount == '') {
                this.discountAmount = 0;
            }
            //Find out current value. If it isn't calculated we consider it as 1 (default value)
            var value = this.value();
            if (typeof value == 'undefined') {
                value = 1;
            }

            //Calculate message to show.
            if (value == 1) {
                var amount = formatPrice.formatPrice(this.discountAmount);
                notice = discountAmountComponent.currencySymbol + amount;
            } else if (value == 2) {
                notice = this.discountAmount + discountAmountComponent.percentSymbol;
            }

            var optionLabel = this.getOption(value).label;

            if (notice != '') {
                this.notice = notice + ' ' + optionLabel + ' ';
                this.notice += $.mage.__('discount will be offered for all recurring options below.');
            }

            $('#'+this.noticeId).html(this.notice);
        }
    });
});
