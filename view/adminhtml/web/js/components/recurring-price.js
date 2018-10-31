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
            this.changeCommentAndValue();

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.changeCommentAndValue();
        },

        /**
         * Fires to change comment.
         */
        changeCommentAndValue: function() {
            //Get current price format
            var priceFormat = this.getPriceFormat(),
                notice = '',
                discountAmount = 0,
                discountTypeValue = '',
                recurringPrice = this.value(), //Get current component integer value,
                productPrice = this.getProductPriceComponentValue(),
                productPriceComponent = registry.get('index=price'),
                lockPriceComponent = registry.get('index=tnw_subscr_lock_product_price'),
                offerDiscountComponent = registry.get('index=tnw_subscr_offer_flat_discount'),
                discountAmountComponent = registry.get('index=tnw_subscr_discount_amount'),
                discountTypeComponent = registry.get('index=tnw_subscr_discount_type');
                priceFormat.pattern = '%s';
            if (typeof recurringPrice === 'string') {
                recurringPrice = formatPrice.formatToNumber(recurringPrice, priceFormat);
            }
            //if price field is disabled - don't display discount fields
            if (!productPriceComponent || productPriceComponent.disabled()) {
                offerDiscountComponent.checked(false);
                offerDiscountComponent.visible(false);
            }

            if (lockPriceComponent.checked()) {         // Product price is locked
                recurringPrice = productPrice;

                if (offerDiscountComponent.checked()) { // Offer flat discount is enabled
                    if (typeof discountAmountComponent != 'undefined') {
                        discountAmount = discountAmountComponent.value();
                    }

                    if (typeof discountAmount == 'undefined' || discountAmount == '') {
                        discountAmount = 0;
                    } else if (typeof discountAmount == 'string') {
                        discountAmount = formatPrice.formatToNumber(discountAmount, priceFormat);
                    }

                    if (discountAmount != 0) {
                        discountTypeValue = discountTypeComponent.value();

                        if (typeof discountTypeValue == 'undefined') {
                            discountTypeValue = 1;
                        }

                        notice += $.mage.__('Estimated') + ' ';
                        if (discountTypeValue == 1) {        //discount type = Flat Fee
                            recurringPrice = recurringPrice - discountAmount;
                            discountAmount = formatPrice.formatPrice(discountAmount, priceFormat);
                            notice += discountAmountComponent.currencySymbol + discountAmount;
                        } else if (discountTypeValue == 2) { //discount type = percent
                            recurringPrice = recurringPrice * (100 - discountAmount)/100;
                            notice += '~' + discountAmount + discountAmountComponent.percentSymbol;
                        }
                    }
                } else {  // Offer flat discount is disabled
                  // no notice
                }
            } else {  //Product price is unlocked
                if ((recurringPrice != 0) && (productPrice != 0)) {
                    discountAmount = productPrice - recurringPrice;
                    if (discountAmount > 0) {
                        discountAmount = formatPrice.formatPrice(discountAmount, priceFormat);
                        notice += $.mage.__('Estimated');
                        notice += ' ' + productPriceComponent.addbefore + discountAmount;
                    }
                }
            }

            this.value(formatPrice.formatPrice(recurringPrice, priceFormat));

            if (notice != '') {     //if calculated notice isn't empty we form whole necessary message to show
                notice += ' ' + $.mage.__('savings to the customer');
            } else {
                notice = ' ';
            }

            this.notice = notice;

            $('#'+this.noticeId).children().html(this.notice);

        },

        /**
         * Return current price format.
         *
         * @returns {*}
         */
        getPriceFormat: function() {
            var priceFormat = null;
            if (typeof this.priceFormat != 'undefined' && this.priceFormat != null) {
                priceFormat = $.parseJSON(this.priceFormat);
            }

            return priceFormat;
        },

        /**
         * Return product price integer value
         *
         * @returns {number}
         */
        getProductPriceComponentValue: function() {
            var priceFormat = this.getPriceFormat();
            var productPrice = 0;
            var productPriceComponent = registry.get('index=price');

            if ((typeof productPriceComponent != 'undefined')
                && (typeof productPriceComponent.value() != 'undefined')) {
                productPrice = formatPrice.formatToNumber(productPriceComponent.value(), priceFormat);
            }

            return productPrice;
        },

        /**
         * Fires to change comment after 'Lock product price' is checked.
         */
        changeCommentLockPrice: function (checked) {
            var lockPriceComponent = registry.get('index=tnw_subscr_lock_product_price');
            if (lockPriceComponent.valueChangedByUser && !checked) {
                var priceFormat = this.getPriceFormat();
                var productPrice = this.getProductPriceComponentValue();
                this.value(formatPrice.formatPrice(productPrice, priceFormat));
            }
            this.changeCommentAndValue();
        },

        /**
         * Fires to change comment after 'Offer flat discount' is checked.
         */
        changeCommentOfferDiscount: function () {
            this.changeCommentAndValue();
        },

        /**
         * Fires to change comment after 'Discount amount' is changed.
         */
        changeCommentDiscountAmount: function () {
            this.changeCommentAndValue();
        },

        /**
         * Fires to change comment after 'Discount type' is changed.
         */
        changeCommentDiscountType: function () {
            this.changeCommentAndValue();
        }
    });
});
