/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    return function (originalAddToCart) {
        $.widget('mage.catalogAddToCart', originalAddToCart, {

            options: {
                subscribeButtonText : $t('Auto-Ship')
            },

            enableAddToCartButton: function (form) {
                var addToCartButtonTextAdded = this.options.addToCartButtonTextAdded || $t('Added'),
                    self = this,
                    addToCartButton = $(form).find(this.options.addToCartButtonSelector);

                addToCartButton.find('span').text(addToCartButtonTextAdded);
                addToCartButton.attr('title', addToCartButtonTextAdded);

                setTimeout(function () {
                    var addToCartButtonTextDefault = self.options.addToCartButtonTextDefault || $t('Add to Cart'),
                        subscribeButtonText = self.options.subscribeButtonText;

                    addToCartButton.removeClass(self.options.addToCartButtonDisabledClass);

                    addToCartButton.each(function () {
                        $(this).attr('id') === 'product-subscribe-button'
                            ? $(this).attr('title', subscribeButtonText).find('span').text(subscribeButtonText)
                            : $(this).attr('title', addToCartButtonTextDefault)
                                .find('span').text(addToCartButtonTextDefault);
                    })
                }, 1000);
            }
        });
        return $.mage.catalogAddToCart;
    }
})
