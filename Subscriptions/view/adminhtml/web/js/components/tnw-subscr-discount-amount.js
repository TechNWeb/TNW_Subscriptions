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

            //calculate discount amount
            var discountAmount = this.value();

            //if discountAmount field is empty we consider it as 0
            if (discountAmount == '') {
                discountAmount = 0;
            }

            //Calculate discount type from the field tnw_subscr_discount_type
            //If it isn't calculated we consider it as 1 (default value)
            var discountTypeComponent = registry.get('index=tnw_subscr_discount_type');
            var value = discountTypeComponent.value();
            if (typeof value == 'undefined') {
                value = 1;
            }

            //Calculate message to show.
            if (value == 1) {
                // currency
                var amount = formatPrice.formatPrice(discountAmount);
                notice = this.currencySymbol + amount;
            } else if (value == 2) {
                // percent
                notice = discountAmount + this.percentSymbol;
            }

            var optionLabel = discountTypeComponent.getOption(value).label;

            if (notice != '') {
                this.notice = notice + ' ' + optionLabel + ' ';
                this.notice += $.mage.__('discount will be offered for all recurring options below.');
            }

            $('#'+this.noticeId).html(this.notice);

            // Update discount amount sign ($ or %) depends on discount_type
            if (value == 1) {
                // currency
                this.addbefore = this.currencySymbol;
            } else if (value == 2) {
                // percent
                this.addbefore = this.percentSymbol;
            }
            $('div.admin__control-addon label[for="' + this.uid + '"] span').html(this.addbefore);
        }
    });
});
