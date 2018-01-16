/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'jquery/ui',
    'Magento_ConfigurableProduct/js/configurable'
], function($){

    $.widget('tnw.configurable', $.mage.configurable, {

        /**
         * Callback which fired after gallery gets initialized.
         *
         * @param {HTMLElement} element - DOM element associated with gallery.
         */
        _onGalleryLoaded: function (element) {
            var galleryObject = element.data('gallery');

            this.options.mediaGalleryInitial = galleryObject.returnCurrentImages();
            this._changeProductImage();
        }
    });

    return $.tnw.configurable;
});
