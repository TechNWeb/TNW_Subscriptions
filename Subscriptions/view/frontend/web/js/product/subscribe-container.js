/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'mage/validation'
], function ($, $t) {
    'use strict';

    $.widget('mage.tnwSubscribeContainer', {
        options: {
            formSelector: '#product_addtocart_form',
            addToCartButtonSelector: '#product-addtocart-button',
            subscribeButtonSelector: '#product-subscribe-button',
            subscribeUrl: '#',
            untilCancelledInputSelector: '#term',
            periodControlSelector: '#period-field',
            setQtyFromFrequency: false,
            frequencyInputSelector: 'input[name="billing_frequency"]',
            qtyInputSelector: '#subscribe_qty',
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: '.stock.available',
            buttonDisabledClass: 'disabled',
            buttonTextWhileAdding: '',
            buttonTextAdded: '',
            buttonTextDefault: ''
        },

        /**
         * Initialize widget
         */
        _create: function () {
            this._bind();
        },

        /**
         * Event binding
         */
        _bind: function () {
            var widget = this,
                button = $(this.options.subscribeButtonSelector),
                untilCancelledInput = $(this.options.untilCancelledInputSelector),
                frequencyInput = $(this.options.frequencyInputSelector);

            button.on('click', $.proxy(function() {
                widget._submitForm();
            }, this));

            untilCancelledInput.on('change', $.proxy(function() {
                widget._togglePeriod();
            }, this));

            if (this.options.setQtyFromFrequency) {
                frequencyInput.on('change', $.proxy(function () {
                    widget._updateQtyFromFrequency();
                }, this));
            }
        },

        /**
         * Set preset qty from frequency into qty input
         */
        _updateQtyFromFrequency: function () {
            var currentFrequency = $(this.options.frequencyInputSelector + ':checked'),
                qtyInput = $(this.options.qtyInputSelector);
            qtyInput.val(Number(currentFrequency.data('preset-qty')));
        },

        /**
         * Show/hide period input
         */
        _togglePeriod: function () {
            var untilCancelledInput = $(this.options.untilCancelledInputSelector),
                periodControl = $(this.options.periodControlSelector);
            if (untilCancelledInput.is(':checked')) {
                periodControl.hide();
            } else {
                periodControl.show();
            }
        },

        /**
         * Submit product form
         */
        _submitForm: function () {
            var self = this,
                form = $(this.options.formSelector);
            
            if (!form.validation('isValid')) {
                return;
            }

            self.disableCartButton(form);

            $.ajax({
                url: this.options.subscribeUrl,
                data: form.serialize(),
                type: 'post',
                dataType: 'json',

                /**
                 * Called when request succeeds
                 *
                 * @param {Object} response
                 */
                success: function(response) {
                    if (response.redirectUrl) {
                        window.location = response.redirectUrl;
                        return;
                    }
                    /*if (response.message) {
                        $(self.options.messagesSelector).html(response.message);
                    }*/
                    if (response.minicart) {
                        $(self.options.minicartSelector).replaceWith(response.minicart);
                        $(self.options.minicartSelector).trigger('contentUpdated');
                    }
                    if (response.product && response.product.statusText) {
                        $(self.options.productStatusSelector)
                            .removeClass('available')
                            .addClass('unavailable')
                            .find('span')
                            .html(response.product.statusText);
                    }
                    self.enableCartButton(form);
                }
            });
        },

        disableCartButton: function(form) {
            var textWhileAdding = this.options.buttonTextWhileAdding || $t('Adding...'),
                subscribeButton = $(form).find(this.options.subscribeButtonSelector),
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            subscribeButton.addClass(this.options.buttonDisabledClass);
            subscribeButton.find('span').text(textWhileAdding);
            subscribeButton.attr('title', textWhileAdding);
            addToCartButton.addClass(this.options.buttonDisabledClass);
        },

        enableCartButton: function(form) {
            var textAdded = this.options.buttonTextAdded || $t('Added');
            var self = this,
                subscribeButton = $(form).find(this.options.subscribeButtonSelector),
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            subscribeButton.find('span').text(textAdded);
            subscribeButton.attr('title', textAdded);

            setTimeout(function() {
                var textDefault = self.options.buttonTextDefault || $t('Add to Cart');
                subscribeButton.removeClass(self.options.buttonDisabledClass);
                subscribeButton.find('span').text(textDefault);
                subscribeButton.attr('title', textDefault);
                addToCartButton.removeClass(self.options.buttonDisabledClass);
            }, 1000);
        }
    });

    return $.mage.tnwSubscribeContainer;
});
