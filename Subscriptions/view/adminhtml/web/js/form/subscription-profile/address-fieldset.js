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
            previousAddressId: null
        },

        /**
         * Hide addresses list select, show address form.
         */
        openAddressForm: function () {
            var button = registry.get('index=add_new_address_button');
            this.setAddressSelectVisibility(false);
            this.fiterEmptyAddressOption(true);
            $j( "[data-index=region_id_input" ).removeClass('hidden');
            var countryId = registry.get('index=country_id');
            countryId.value('US');
        },

        /**
         * Show addresses list select, hide address form, clear form fields data.
         */
        closeAddressForm: function () {
            this.fiterEmptyAddressOption(false);
            this.setAddressSelectVisibility(true);
            this.clearElemsData();
            $j( "[data-index=region_id_input" ).addClass('hidden');
        },

        /**
         * Show/hide addresses list select.
         *
         * @param visibility
         */
        setAddressSelectVisibility: function (visibility) {
            var addressSelect = this.getAddressSelect();

            addressSelect.visible(visibility);

            if (!addressSelect.visible()) {
                this.previousAddressId = addressSelect.value();
            } else if (this.previousAddressId) {
                addressSelect.value(this.previousAddressId);
            }
        },

        /**
         * Fill addresses list select with options.
         *
         * @param value
         */
        fiterEmptyAddressOption: function(value) {
            var addressSelect = this.getAddressSelect();
            addressSelect.filter(value, 'empty');
        },

        /**
         * Return addresses list select component.
         *
         * @returns {*}
         */
        getAddressSelect: function () {
            return registry.get('index=customer_address_id');
        },

        /**
         * Clear form fields data.
         */
        clearElemsData: function () {
            var children = this.elems();
            var notUpdatableElems = this.notUpdatableElems;

            children.forEach(function(item, i, arr) {
                if (item.index != 'customer_address_id' && (notUpdatableElems.indexOf(item.formElement) == -1)) {
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
