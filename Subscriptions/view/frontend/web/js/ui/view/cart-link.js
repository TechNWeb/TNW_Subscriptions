/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery'
], function (Component, customerData, $) {
    'use strict';

    return Component.extend({
        defaults: {
            dialog: null,
            cartLink: ".tnw-subscriptions-cart-link"
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();

            this.subscriptionCart = customerData.get('tnw-subscriptions-subscription-cart');
        },

        /**
         * Show empty subscription cart popup or redirect to subscription cart page if it is not empty.
         *
         * @param {Object} element
         * @param {Object} event
         * @return {boolean}
         */
        showPopup: function(element, event) {
            var self = this,
                result = false;

            if (self.subscriptionCart().summary_count === 0) {
                if (!this.dialog) {
                    this.createDialog();
                    this.dialog.dropdownDialog('open');
                    $('body').on('click.outsideDropdown', function() {
                        if(!self.dialog.dropdownDialog("isOpen") && !$(event.target).closest('.ui-dialog').length) {
                            self.dialog.dropdownDialog('open');
                        }
                    }.bind(this));
                }
            } else {
                if (self.dialog !== null) {
                    $(this.cartLink).off();
                    self.dialog.dropdownDialog('destroy');
                    self.dialog = null;
                    $('img.tnw-subscriptions-minicart').click();
                }

                result = true;
            }

            return result;
        },

        /**
         * Hide subscription empty cart popup.
         */
        hidePopup: function() {
          if (this.dialog !== null) {
              this.dialog.dropdownDialog('close');
          }
        },

        /**
         * Create subscription empty cart popup.
         */
        createDialog: function() {
            this.dialog = $('.tnw-subscriptions-cart-empty');
            this.dialog.dropdownDialog({
                "appendTo": "[data-role=tnw-subscriptions-cart-link]",
                "triggerEvent":"click",
                "triggerTarget": this.cartLink,
                "timeout": "2000",
                "closeOnMouseLeave": false,
                "closeOnEscape": true,
                "triggerClass": "active",
                "parentClass": "active",
                "buttons": []
            });
        }
    });
});
