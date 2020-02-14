/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
], function ($, $t,alert) {
    'use strict';

    return function (widget) {
        $.widget('mage.catalogAddToCart', widget, {

            enableAddToCartButton: function (form) {
                this.options.addToCartButtonTextDefault = this.options.addToCartButtonTextDefault || $t('Subscribe');
                this._super(form);
            }
        });

        return $.mage.catalogAddToCart;
    }
});