/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
            methodsListSelector: '.methods-list',
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
                methodsList = $(this.options.methodsListSelector),
                methods = methodsList.find('input[type=radio]'),
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
         * Save form data.
         *
         * @param {EventObject} e
         * @returns void
         */
        save: function(e) {
            var form = $(this.options.formSelector),
                widget = this,
                saveButton = $(this.options.saveButton),
                cancelButton = $(this.options.cancelButton);

            if (form.valid()) {
                $('body').trigger('processStart');
                this.disableButton(saveButton);
                this.disableButton(cancelButton);

                $.ajax({
                    url: this.options.saveUrl,
                    data: form.serialize(),
                    type: 'post',
                    dataType: 'json',

                    /**
                     * Called when request succeeds
                     *
                     * @param {Object} response
                     */
                    success: function(response) {
                        var methodBlock = $(widget.options.blockContent);

                        if (typeof response.data.shipping_details !== 'undefined') {
                            methodBlock.html(response.data.shipping_details);
                        }
                        widget.setFormsVisibility(false);
                        widget.enableButton(saveButton);
                        widget.enableButton(cancelButton);
                    }
                }).always(function() {
                    $('body').trigger('processStop');
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
            this._setElemsVisibility($(this.options.viewSelector), !showEdit);
            this._setElemsVisibility($(this.options.editSelector), showEdit);
            this._setElemsVisibility($(this.options.editButton), !showEdit);
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
                elem.removeClass('hidden');
            } else {
                elem.hide();
            }
        }
    });

    return $.mage.tnwSubscribeShipmentDetails;
});
