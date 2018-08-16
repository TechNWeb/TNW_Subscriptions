/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'TNW_Subscriptions/js/checkout/model/shipping-address/form-popup-state'
], function ($, ko, Component, customerData, formPopUpState) {
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
                return false;
            }, this);

            return this;
        },

        /**
         * @param {String} countryId
         * @return {String}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] != undefined ? countryData()[countryId].name : ''; //eslint-disable-line
        },

        /** Set selected customer shipping address  */
        selectAddress: function () {

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
            $('[data-open-modal="opc-new-shipping-address"]').trigger('click');
        }
    });
});
