/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(['jquery'], function($) {
    return function (originalConfigurable) {
        $.widget('mage.configurable', originalConfigurable, {

            options: {
                selectorProductPrice: '[data-role=priceBox]:not(.subscription-price-container)',
            },

            /**
             * Callback which fired after gallery gets initialized.
             *
             * @param {HTMLElement} element - DOM element associated with gallery.
             */
            _onGalleryLoaded: function (element) {
                this._super(element);
                this._changeProductImage();
            }
        });

        return $.mage.configurable;
    }
});
