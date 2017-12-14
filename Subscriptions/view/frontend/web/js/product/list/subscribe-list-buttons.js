/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
], function ($j) {
    'use strict';

    $j.widget('mage.tnwSubscribeListButtons', {
        options: {
            subscriptionDropdownBlock: '.product-subscribe-button-dropdown',
            subscriptionDropdownButtonSelector: '.product-subscribe-button-dropdown button',
            subscriptionDropdownButtonsActive: '.product-addtocart-button-hidden',
            currentSubscriptionDropdownButtonsActive: '.subscription-dropdown-hidden-',
            wishlistSubscriptionDropDownActive: '.wishlist-subscription-dropdown-hidden-'
        },

        /**
         * Initialize widget.
         */
        _create: function () {
            this._bind();
        },

        /**
         * Event binding.
         */
        _bind: function () {
            var widget = this;
            $j(this.options.subscriptionDropdownButtonSelector).on('click', function() {
                widget._showDropdownContainer(this);
            });

            $j(".block-wishlist").on("click",this.options.subscriptionDropdownButtonSelector, function() {
                widget._showDropdownContainerWishlist(this);
            });
        },

        /**
         * Show drop down container.
         */
        _showDropdownContainer: function (elem) {
            var jElem = $j(elem);
            var prodId = jElem.data('product-id');
            if (!jElem.hasClass('active')) {
                jElem.addClass('active');

                $j(this.options.currentSubscriptionDropdownButtonsActive + prodId).addClass('active');
            } else {
                jElem.removeClass('active');
                $j(this.options.currentSubscriptionDropdownButtonsActive + prodId).removeClass('active');
            }
        },

        /**
         * Show drop down container wishlist block.
         */
        _showDropdownContainerWishlist: function (elem) {
            var jElem = $j(elem);
            var itemId = jElem.data('wishlist-item-counter');
            if (!jElem.hasClass('active')) {
                jElem.addClass('active');

                $j(this.options.wishlistSubscriptionDropDownActive + itemId).addClass('active');
            } else {
                jElem.removeClass('active');
                $j(this.options.wishlistSubscriptionDropDownActive + itemId).removeClass('active');
            }
        }
    });

    return $j.mage.tnwSubscribeListButtons;
});
