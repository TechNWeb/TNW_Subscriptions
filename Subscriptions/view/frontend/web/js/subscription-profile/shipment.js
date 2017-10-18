/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.tnwSubscribeShipment', {
        options: {
            saveAddressUrl: '#',
            showEdit: 0,
            customerAddressesData: [],
            formSelector: '#shipping-address-form',
            infoViewSelector: '#shipping-info-view',
            infoEditSelector: '#shipping-info-edit',
            customerDataFieldSet: '#customer-data',
            addressEditButton: '#shipping-edit-button',
            addressFieldsList: '#shipping-fields-list',
            customerAddressesList: '#customer_address_id',
            addNewAddressButton: '#add-new',
            pickFromSavedButton: '#pick',
            cancelButton: '#cancel-save',
            saveAddressButton: '#save_address',
            addressFields: '.address-field',
            infoFields: '.info-field',
            defaultCountryId: 'US'
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
         */
        _initialize: function () {
            var showEdit = this.options.showEdit;
            this.setFormsVisibility(showEdit);
            var customerAddresses = $(this.options.customerAddressesList);
            var addressSelectVisibility = false;

            if (customerAddresses && customerAddresses.val() * 1) {
                addressSelectVisibility = true;
            }
            this.setAddressFieldsVisibility(addressSelectVisibility);
        },

        /**
         * Event binding
         */
        _bind: function () {
            var widget = this,
                editButton = $(this.options.addressEditButton),
                addNewButton = $(this.options.addNewAddressButton),
                pickFromSavedButton = $(this.options.pickFromSavedButton),
                cancelButton = $(this.options.cancelButton),
                saveAddressButton = $(this.options.saveAddressButton),
                customerDataFieldSet = $(this.options.customerDataFieldSet),
                customerAddressesSelect = $(this.options.customerAddressesList),
                defaultValue = '';

            editButton.on('click', $.proxy(function() {
                widget.setFormsVisibility(false);
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
                widget.setFormsVisibility(true);
            }, this));
            saveAddressButton.on('click', $.proxy(function(e) {
                e.stopPropagation();
                e.preventDefault();
                widget.saveAddress(e);
            }, this));
        },

        /**
         * Fill address form inputs with data from customer addresses.
         *
         * @param customerAddressesSelect
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
         * @param e
         */
        saveAddress: function(e) {
            var form = $(this.options.formSelector);

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

                    //@toDo make response validation

                }
            });
        },

        /**
         * Display/hide info/edit forms.
         *
         * @param showEdit
         */
        setFormsVisibility: function(showEdit) {
            this.setElemsVisibility($(this.options.infoViewSelector), showEdit);
            this.setElemsVisibility($(this.options.infoEditSelector), !showEdit);
        },

        /**
         * Display/hide address fields.
         *
         * @param visibility
         */
        setAddressFieldsVisibility: function (visibility) {
            this.setElemsVisibility($(this.options.customerAddressesList), visibility);
            this.setElemsVisibility($(this.options.addressFieldsList), !visibility);
            if ($(this.options.customerAddressesList+' option').size() > 1) {
                this.setElemsVisibility($(this.options.pickFromSavedButton), !visibility);
                this.setElemsVisibility($(this.options.addNewAddressButton), visibility);
            } else {
                this.setElemsVisibility($(this.options.pickFromSavedButton), false);
                this.setElemsVisibility($(this.options.addNewAddressButton), false);
            }
        },

        /**
         * Display/hide field.
         *
         * @param elem
         * @param visible
         */
        setElemsVisibility: function (elem, visible) {
            if (visible) {
                elem.show();
            } else {
                elem.hide();
            }
        }

    });

    return $.mage.tnwSubscribePrice;
});
