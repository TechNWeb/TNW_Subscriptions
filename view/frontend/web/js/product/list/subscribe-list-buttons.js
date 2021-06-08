/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
], function ($) {
    'use strict';

    $.widget('mage.tnwSubscribeListButtons', {
        options: {
            subscriptionDropdownBlock: '.product-subscribe-button-dropdown',
            subscriptionDropdownButtonSelector: '.product-subscribe-button-dropdown button',
            subscriptionDropdownButtonsActive: '.product-addtocart-button-hidden',
            currentSubscriptionDropdownButtonsActive: '.subscription-dropdown-hidden-',
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
            this.element.find(this.options.subscriptionDropdownButtonSelector).on('click', function (e) {
                e.preventDefault();
                widget._showDropdownContainer(this);
            });
        },

        /**
         * Show drop down container.
         */
        _showDropdownContainer: function (elem) {
            var jElem = $(elem);
            var index = jElem.data('product-id') | jElem.data('wishlist-item-counter');
            if (!jElem.hasClass('active')) {
                jElem.addClass('active');

                this.element.find(this.options.currentSubscriptionDropdownButtonsActive + index).addClass('active');
            } else {
                jElem.removeClass('active');
                this.element.find(this.options.currentSubscriptionDropdownButtonsActive + index).removeClass('active');
            }
        }
    });

    return $.mage.tnwSubscribeListButtons;
});
