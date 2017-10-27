/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
/*jquery:true*/
define([
    'jquery',
    'mage/validation'
], function ($) {
    'use strict';

    $.widget('mage.tnwSubscribeBilling', {
        options: {
            saveAddressUrl: '#',
            showEdit: 0,
            customerAddressesData: [],
            formSelector: '#billing-address-form',
            infoViewSelector: '#billing-info-view',
            infoEditSelector: '#billing-info-edit',
            customerDataFieldSet: '#customer-data',
            addressEditButton: '#shipping-edit-button',
            addressFieldsList: '#billing-fields-list',
            customerAddressesList: '#customer_address_id',
            addNewAddressButton: '#add-new',
            pickFromSavedButton: '#pick',
            cancelButton: '#cancel-save',
            addressFields: '.address-field',
            infoFields: '.info-field',
            defaultCountryId: 'US',
            countrySelect: '#country',
            emptyCountryLabel: '',
            infoBlockContent: '.subscription-profile-billing-address',
            buttonDisabledClass: 'disabled',
            requiredFields: 'required'
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
            var showEdit = this.options.showEdit * 1 ,
                customerAddresses = $(this.options.customerAddressesList),
                addressSelectVisibility = false;

            this._setCountrySelectEmptyLabel();
            this.setFormsVisibility(showEdit);

            if (customerAddresses && customerAddresses.val() * 1) {
                addressSelectVisibility = true;
            }
            this.setAddressFieldsVisibility(addressSelectVisibility);
        },

        /**
         * Event binding
         *
         * @private
         * @returns void
         */
        _bind: function () {
            var widget = this,
                editButton = $(this.options.addressEditButton),
                addNewButton = $(this.options.addNewAddressButton),
                pickFromSavedButton = $(this.options.pickFromSavedButton),
                cancelButton = $(this.options.cancelButton),
                customerDataFieldSet = $(this.options.customerDataFieldSet),
                customerAddressesSelect = $(this.options.customerAddressesList),
                defaultValue = '',
                form =$(this.options.formSelector),
                requiredFields = $(this.options.requiredFields);

            editButton.on('click', $.proxy(function() {
                widget.setFormsVisibility(true);
            }, this));
            addNewButton.on('click', $.proxy(function() {
                widget.setAddressFieldsVisibility(false);
                $.each($(widget.options.addressFieldsList).find(this.options.addressFields), function (key, field) {
                    if ($(field).attr('id') === 'country') {
                        widget.updateFieldValue(field, widget.options.defaultCountryId);
                    } else {
                        widget.updateFieldValue(field, defaultValue);
                    }
                });
            }, this));
            pickFromSavedButton.on('click', $.proxy(function() {
                widget.setAddressFieldsVisibility(true);
            }, this));
            $.each(customerDataFieldSet.find('input'), function (key, field) {
                $(field).on('change', $.proxy(function() {
                    widget.setAddressFieldsVisibility(false);
                }, this));
            });
            customerAddressesSelect.on('change', $.proxy(function() {
                widget.fillInputsData(customerAddressesSelect);
            }, this));
            cancelButton.on('click', $.proxy(function(e) {
                e.stopPropagation();
                e.preventDefault();
                widget.setFormsVisibility(false);
            }, this));
            $.each(form.find('.required'), function (key, field) {
                $(field).on('focusout', $.proxy(function() {
                    form.valid();
                }, this));
            });
            form.submit(function( e ) {
                e.stopPropagation();
                e.preventDefault();
                widget.saveAddress(e);
            });
        },

        /**
         * Set empty label for countries select.
         *
         * @private
         */
        _setCountrySelectEmptyLabel: function() {
            $(this.options.countrySelect+ ' option[value=""]').text(this.options.emptyCountryLabel);
        },

        /**
         * Fill address form inputs with data from customer addresses.
         *
         * @param {jQuery} customerAddressesSelect
         * @returns void
         */
        fillInputsData: function(customerAddressesSelect) {
            var widget = this,
                selectedOptionVal = customerAddressesSelect.val(),
                addressesData = this.options.customerAddressesData.replace(/'/g,'"'),
                form = $(this.options.formSelector),
                selectedAddressData = [];

            addressesData = JSON.parse(addressesData);

            if (typeof addressesData[selectedOptionVal] != 'undefined') {
                selectedAddressData = addressesData[selectedOptionVal];

                $.each(form.find(this.options.infoFields), function (key, field) {
                    widget.updateFieldValue(field, selectedAddressData[field.id]);
                });
                $.each(form.find(this.options.addressFields), function (key, field) {
                    widget.updateFieldValue(field, selectedAddressData[field.id]);
                });
            }
        },

        /**
         * Update input or select field value.
         *
         * @param field
         * @param fieldValue
         * @returns void
         */
        updateFieldValue: function(field, fieldValue) {
            var fieldElem = $(field);

                fieldElem.val(fieldValue);
                if ($(field).hasClass('address-field')) {
                    fieldElem.change();
                }
        },

        /**
         * Save address.
         *
         * @param {EventObject} e
         * @returns void
         */
        saveAddress: function(e) {
            var form = $(this.options.formSelector),
                widget = this,
                editButton = $(this.options.addressEditButton),
                addNewButton = $(this.options.addNewAddressButton);

            if (form.valid()) {
                if (!$(this.options.customerAddressesList).is(':visible')) {
                    $(this.options.customerAddressesList).val('0');
                }
                this.disableButton(editButton);
                this.disableButton(addNewButton);

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
                        var addressBlock = $(widget.options.infoBlockContent).find('address');

                        if (typeof response.data.billing_address != 'undefined') {
                            addressBlock.html(response.data.billing_address);
                        }
                        widget.setFormsVisibility(false);
                        widget.enableButton(editButton);
                        widget.enableButton(addNewButton);
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
         * Display/hide address fields.
         *
         * @param {bool} visibility
         * @returns void
         */
        setAddressFieldsVisibility: function (visibility) {
            this._setElemsVisibility($(this.options.customerAddressesList), visibility);
            this._setElemsVisibility($(this.options.addressFieldsList), !visibility);
            if ($(this.options.customerAddressesList +' option').length > 1) {
                this._setElemsVisibility($(this.options.pickFromSavedButton), !visibility);
                this._setElemsVisibility($(this.options.addNewAddressButton), visibility);
            } else {
                this._setElemsVisibility($(this.options.pickFromSavedButton), false);
                this._setElemsVisibility($(this.options.addNewAddressButton), false);
            }
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

    return $.mage.tnwSubscribePrice;
});
