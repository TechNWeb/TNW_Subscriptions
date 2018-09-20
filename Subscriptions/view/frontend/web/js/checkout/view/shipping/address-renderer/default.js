/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'TNW_Subscriptions/js/checkout/data',
    'TNW_Subscriptions/js/checkout/model/quote',
    'TNW_Subscriptions/js/checkout/model/shipping/address/form-popup-state'
], function ($, ko, Component, customerData, data, quote, formPopUpState) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return Component.extend({
        defaults: {
            template: 'TNW_Subscriptions/checkout/shipping/address-renderer/default'
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super();
            this.isSelected = ko.computed(function () {
                var isSelected = false,
                    shippingAddress = quote.shippingAddress();

                if (shippingAddress) {
                    isSelected = shippingAddress.getKey() == this.address().getKey();
                }

                return isSelected;
            }, this);

            return this;
        },

        /**
         * @param {String} countryId
         * @return {String}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] != undefined
                ? countryData()[countryId].name
                : '';
        },

        /** Set selected customer shipping address  */
        selectAddress: function () {
            quote.shippingAddress(this.address());
            data.setSelectedShippingAddress(this.address().getKey());
        },

        /**
         * Edit address.
         */
        editAddress: function () {
            formPopUpState.isVisible(true);
            this.showPopup();
        },

        /**
         * Show popup.
         */
        showPopup: function () {
            $('[data-open-modal="tsc-new-shipping-address"]').trigger('click');
        }
    });
});
