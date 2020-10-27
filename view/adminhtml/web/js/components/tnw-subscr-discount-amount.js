/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'jquery',
    'TNW_Subscriptions/js/formatPrice',
    'Magento_Ui/js/lib/validation/validator',
    'mage/translate',
    'jquery/ui'
], function (Abstract, registry, $, formatPrice, validator) {
    'use strict';

    return Abstract.extend({
        defaults: {
            currentPriceFormat: null
        },

        /**
         * Invokes initialize method of parent class,
         * contains initialization logic
         */
        initialize: function () {
            this._super();

            validator.addRule(
                "discount-less-then-price",
                function (validated) {
                    var discountAmountComponent = registry.get('index=tnw_subscr_discount_amount'),
                        productPriceComponent = registry.get('index=price, dataScope=data.product.price');
                    if (productPriceComponent.disabled()) {
                        //In case of configurable product
                        return true;
                    }
                    return discountAmountComponent.validateDiscountLessPrice(validated);
                },
                $.mage.__('The discount cannot exceed the total product cost.')
            );

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
            var notice = '';
            //Get current price format
            var priceFormat = this.getPriceFormat();
            //calculate discount amount
            var discountAmount = this.value();
            if (typeof discountAmount == 'undefined') {
                discountAmount = this.default;
            }

            if (typeof discountAmount == 'string') {
                discountAmount = formatPrice.formatToNumber(discountAmount, priceFormat);
            }

            //if discountAmount field is empty we consider it as 0
            if (discountAmount == '') {
                discountAmount = 0;
            }

            //Calculate discount type from the field tnw_subscr_discount_type
            //If it isn't calculated we consider it as 1 (default value)
            var discountTypeComponent = this.getDiscountTypeComponent();
            var discountTypeValue = this.getDiscountType();

            //Calculate message to show.
            if (discountTypeValue == 1) {
                // currency
                var amount = formatPrice.formatPrice(discountAmount, priceFormat);
                notice = this.currencySymbol + amount;
            } else if (discountTypeValue == 2) {
                // percent
                notice = discountAmount + this.percentSymbol;
            }

            var optionLabel = discountTypeComponent.getOption(discountTypeValue).label;

            if (notice != '') {
                this.notice = notice + ' ' + optionLabel + ' ';
                this.notice += $.mage.__('discount will be offered for all recurring options below.');
            }

            $('#'+this.noticeId).children().html(this.notice);

            // Update discount amount sign ($ or %) depends on discount_type
            if (discountTypeValue == 1) {
                // currency
                this.addbefore = this.currencySymbol;
            } else if (discountTypeValue == 2) {
                // percent
                this.addbefore = this.percentSymbol;
            }

            this.validate();
            $('div.admin__control-addon label[for="' + this.uid + '"] span').html(this.addbefore);
        },

        //Get current price format
        getPriceFormat: function() {
            if (this.currentPriceFormat == null) {
                if (typeof this.priceFormat != 'undefined' && this.priceFormat != null) {
                    this.currentPriceFormat = $.parseJSON(this.priceFormat);
                }
            }

            return this.currentPriceFormat;
        },

        getDiscountTypeComponent: function() {
            return registry.get('index=tnw_subscr_discount_type');
        },

        getDiscountType: function () {
            var value = this.getDiscountTypeComponent().value();
            if (typeof value == 'undefined') {
                value = 1;
            }

            return value;
        },

        //Validates Discount Value less then product price value.
        validateDiscountLessPrice: function() {
            var validated = true;
            if (this.getPriceWithDiscount() <= 0) {
                validated = false;
            }

            return validated;

        },

        getPriceWithDiscount: function() {
            var productPrice = this.getProductPriceComponentValue();
            var recurringPrice = productPrice;
            var priceFormat = this.getPriceFormat();
            var discountAmount = this.value();
            var discountTypeComponent = this.getDiscountTypeComponent();


            if (typeof discountAmount == 'undefined' || discountAmount == '') {
                discountAmount = 0;
            } else if (typeof discountAmount == 'string') {
                discountAmount = formatPrice.formatToNumber(discountAmount, priceFormat);
            }

            if (discountAmount != 0) {
                var discountTypeValue = this.getDiscountType();

                if (discountTypeValue == 1) {        //discount type = Flat Fee
                    recurringPrice = recurringPrice - discountAmount;
                } else if (discountTypeValue == 2) { //discount type = percent
                    recurringPrice = recurringPrice * (100 - discountAmount)/100;
                }
            }

            return recurringPrice;
        },

        /**
         * Return product price integer value
         *
         * @returns {number}
         */
        getProductPriceComponentValue: function() {
            var priceFormat = this.getPriceFormat();
            var productPrice = 0;
            var productPriceComponent = registry.get('index=price, dataScope=data.product.price');

            if ((typeof productPriceComponent != 'undefined')
                && (typeof productPriceComponent.value() != 'undefined')) {
                productPrice = formatPrice.formatToNumber(productPriceComponent.value(), priceFormat);
            }

            return productPrice;
        },
    });
});
