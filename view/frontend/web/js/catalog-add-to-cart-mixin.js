/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
], function ($, $t) {
    'use strict';

    return function (widget) {
        $.widget('mage.catalogAddToCart', widget, {

            enableAddToCartButton: function (form) {
                let tnwSubscribe = $(form).find(".tnw-subscribe");
                if (tnwSubscribe.length == 1) {
                    this.options.addToCartButtonTextDefault = this.options.addToCartButtonTextDefault || $t('Subscribe');
                } else {
                    this.options.addToCartButtonTextDefault = this.options.addToCartButtonTextDefault || $t('Add to cart');
                }
                this._super(form);
            }
        });

        return $.mage.catalogAddToCart;
    }
});
