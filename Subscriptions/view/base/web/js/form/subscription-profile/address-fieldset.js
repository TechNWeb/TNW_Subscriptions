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
            countryIdSelection: null
        },

        /**
         * Hide addresses list select, show address form.
         *
         * @return void
         */
        openAddressForm: function () {
            this.setAddressSelectVisibility(false);
            this.filterEmptyAddressOption(true);
            $j(this.getPreSelector() + "[data-index=region_id_input]").removeClass('hidden');
            var countryId = registry.get(this.getSelectionForCountryId());
            countryId.value('US');
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
