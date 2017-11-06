/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'jquery'
], function (Collapsible, registry, $j) {
    'use strict';

    return Collapsible.extend({
        defaults: {
            template: 'TNW_Subscriptions/form/subscription-profile/address-fieldset',
            notUpdatableElems: [
                'container',
                'button'
            ],
            previousAddressId: null,
            customerAddressSelector: 'customer_address_id',
            regionIdInputPreSelector: null,
            countryIdSelection: null,
            customerAddressesData: ''
        },

        /**
         * Hide addresses list select, show address form.
         *
         * @return void
         */
        openAddressForm: function (needFillData) {
            var customerAddressesSelect = registry.get('index = ' + this.customerAddressSelector);
            if (customerAddressesSelect && needFillData) {
                this.fillCustomerAddressData(customerAddressesSelect);
            }
            this.setAddressSelectVisibility(false);
            this.filterEmptyAddressOption(true);
            $j(this.getPreSelector() + "[data-index=region_id_input]").removeClass('hidden');
            if (!needFillData) {
                var countryId = registry.get(this.getSelectionForCountryId());
                countryId.value('US');
            }
        },

        /**
         * Show addresses list select, hide address form, clear form fields data.
         *
         * @return void
         */
        closeAddressForm: function () {
            this.filterEmptyAddressOption(false);
            this.setAddressSelectVisibility(true);
            this.clearElemsData();
            $j(this.getPreSelector() + "[data-index=region_id_input]").addClass('hidden');
        },

        /**
         * Fill customer address fields from saved address.
         *
         * @param customerAddressesSelect
         * @return void
         */
        fillCustomerAddressData: function (customerAddressesSelect) {

            var addressesData = this.customerAddressesData.replace(/'/g,'"'),
                selectedOptionVal = customerAddressesSelect.value(),
                current = this;

            addressesData = JSON.parse(addressesData);

            if (typeof addressesData[selectedOptionVal] !== 'undefined') {
                var selectedAddressData = addressesData[selectedOptionVal];

                $j.each(this.elems(), function (key, field) {
                    current.updateFieldValue(field, selectedAddressData[field.index]);
                });
            }

        },

        /**
         * Update input or select field value.
         *
         * @param field
         * @param fieldValue
         * @return void
         */
        updateFieldValue: function(field, fieldValue) {
            if (fieldValue === null || fieldValue === undefined) {
                return;
            }
            field.value(fieldValue);
        },

        /**
         * Get pre selector for region input
         *
         * @return {string}
         */
        getPreSelector: function() {
            return this.regionIdInputPreSelector
                ? "[data-index=" + this.regionIdInputPreSelector + "] "
                : "" ;
        },

        /**
         * Get parent selection for country id field
         *
         * @return {string}
         */
        getSelectionForCountryId: function() {
            return this.countryIdSelection
                ? 'inputName=' + this.countryIdSelection + '[country_id]'
                : 'index=country_id';
        },

        /**
         * Get parent selection for region id field
         *
         * @return {String}
         */
        getSelectionForRegionId: function () {
            return this.countryIdSelection
            ? 'inputName=' + this.countryIdSelection + '[region_id]'
            : 'index=region_id';
        },

        /**
         * Show/hide addresses list select.
         *
         * @param {boolean} visibility
         * @return void
         */
        setAddressSelectVisibility: function (visibility) {
            var addressSelect = this.getAddressSelect();

            if (addressSelect) {
                addressSelect.visible(visibility);

                if (!addressSelect.visible()) {
                    this.previousAddressId = addressSelect.value();
                } else if (this.previousAddressId) {
                    addressSelect.value(this.previousAddressId);
                }
            }
        },

        /**
         * Fill addresses list select with options.
         *
         * @param {boolean} value
         * @return void
         */
        filterEmptyAddressOption: function(value) {
            var addressSelect = this.getAddressSelect();
            if (addressSelect) {
                addressSelect.filter(value, 'empty');
            }
        },

        /**
         * Return addresses list select component.
         *
         * @returns {*}
         */
        getAddressSelect: function () {
            return registry.get('index=' + this.customerAddressSelector);
        },

        /**
         * Clear form fields data.
         *
         * @return void
         */
        clearElemsData: function () {
            var children = this.elems(),
                notUpdatableElems = this.notUpdatableElems,
                selector = this.customerAddressSelector;

            children.forEach(function(item, i, arr) {
                if (item.index != selector && (notUpdatableElems.indexOf(item.formElement) == -1)) {
                    if (item.formElement == 'checkbox') {
                        item.checked(false);
                    } else if (typeof item.value() != 'undefined') {
                        item.value('');
                    }
                }
            });
        }
    });
});
