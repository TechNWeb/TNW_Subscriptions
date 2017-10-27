/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.tnwSubscribeShipmentDetails', {
        options: {
            saveUrl: '#',
            showEdit: 0,
            formSelector: '#shipping-details-form',
            viewSelector: '#shipping-details-view',
            editSelector: '#shipping-details-edit',
            editButton: '#shipping-details-edit-button',
            cancelButton: '#shipping-details-form-cancel',
            saveButton: '#shipping-details-form-save',
            blockContent: '.subscription-profile-shipping-details',
            buttonDisabledClass: 'disabled'
        },

        /**
         * Initialize widget.
         */
        _create: function () {
            this._initialize();
            this._bind();
        },

        /**
         * First initialization.
         *
         * @private
         * @returns void
         */
        _initialize: function () {
            var showEdit = parseInt(this.options.showEdit);
            this.setFormsVisibility(showEdit);
        },

        /**
         * Event binding
         *
         * @private
         * @returns void
         */
        _bind: function () {
            var widget = this,
                editButton = $(this.options.editButton),
                cancelButton = $(this.options.cancelButton),
                defaultValue = '',
                form =$(this.options.formSelector);

            editButton.on('click', $.proxy(function() {
                widget.setFormsVisibility(true);
            }, this));
            cancelButton.on('click', $.proxy(function(e) {
                e.stopPropagation();
                e.preventDefault();
                widget.setFormsVisibility(false);
            }, this));
            form.submit(function( e ) {
                e.stopPropagation();
                e.preventDefault();
                widget.save(e);
            });
        },

        /**
         * Save from data.
         *
         * @param {EventObject} e
         * @returns void
         */
        save: function(e) {
            var form = $(this.options.formSelector),
                widget = this,
                editButton = $(this.options.addressEditButton);

            if (form.valid()) {
                this.disableButton(editButton);

                $.ajax({
                    url: this.options.saveAddressUrl,
                    data: form.serialize(),
                    type: 'post',
                    dataType: 'json',

                    /**
                     * Called when request succeeds
                     *
                     * @param {Object} response
                     */
                    success: function(response) {
                        var addressBlock = $(widget.options.blockContent);//todo

                        if (typeof response.data.shipping_details !== 'undefined') {
                            addressBlock.html(response.data.shipping_details);
                        }
                        widget.setFormsVisibility(true);
                        widget.enableButton(editButton);
                    }
                });
            }
        },

        /**
         * Disable button.
         *
         * @param {jQuery} button
         * @returns void
         */
        disableButton: function(button) {
            button.addClass(this.options.buttonDisabledClass);
        },

        /**
         * Enable button.
         *
         * @param {jQuery} button
         * @returns void
         */
        enableButton: function(button) {
            button.removeClass(this.options.buttonDisabledClass);
        },

        /**
         * Display/hide info/edit forms.
         *
         * @param {bool|string} showEdit
         * @returns void
         */
        setFormsVisibility: function(showEdit) {
            this._setElemsVisibility($(this.options.infoViewSelector), !showEdit);
            this._setElemsVisibility($(this.options.infoEditSelector), showEdit);
        },

        /**
         * Display/hide field.
         *
         * @private
         * @param {jQuery} elem
         * @param {bool|string} visible
         * @returns void
         */
        _setElemsVisibility: function (elem, visible) {
            if (visible) {
                elem.show();
            } else {
                elem.hide();
            }
        }
    });

    return $.mage.tnwSubscribeShipmentDetails;
});
