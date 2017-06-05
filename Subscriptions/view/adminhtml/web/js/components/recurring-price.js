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
            var notice = '';
            var discountAmount = 0;
            var discountType = '';
            var discountTypeValue = '';

            var lockPriceComponent = registry.get('index=tnw_subscr_lock_product_price');
            var offerDiscountComponent = registry.get('index=tnw_subscr_offer_flat_discount');
            var discountAmountComponent = registry.get('index=tnw_subscr_discount_amount');
            var discountTypeComponent = registry.get('index=tnw_subscr_discount_type');

            if (offerDiscountComponent.checked()) { // Offer flat discount is enabled
                discountAmount = discountAmountComponent.value();

                if (typeof discountAmount == 'undefined' || discountAmount == '') {
                    discountAmount = 0;
                }

                if (discountAmount != 0) {
                    discountTypeValue = discountTypeComponent.value();

                    if (typeof discountTypeValue == 'undefined') {
                        discountTypeValue = 1;
                    }

                    discountType = discountTypeComponent.getOption(discountTypeValue);

                    notice += $.mage.__('Estimated') + ' ';
                    if (discountTypeValue == 1) {        //discount type = Flat Fee
                        discountAmount = formatPrice.formatPrice(discountAmount);
                        notice += discountAmountComponent.currencySymbol + discountAmount;
                    } else if (discountTypeValue == 2) { //discount type = percent
                        notice += '~' + discountAmount + discountAmountComponent.percentSymbol;
                    }
                }

            } else {   // Offer flat discount is disabled
                // no notice
            }

            if (notice != '') {     //if calculated notice isn't empty we form whole necessary message to show
                notice += ' ' + $.mage.__('savings to the end consumer');
            } else {
                notice = ' ';
            }

            this.notice = notice;

            $('#'+this.noticeId).html(this.notice);

        },

        /**
         * Fires to change comment after 'Lock product price' is checked.
         */
        changeCommentLockPrice: function () {
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
