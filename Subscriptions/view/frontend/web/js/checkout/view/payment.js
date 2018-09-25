/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'jquery',
    'underscore',
    'uiComponent',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
    'TNW_Subscriptions/js/checkout/model/quote',
    'TNW_Subscriptions/js/checkout/model/payment/service',
    'Magento_Checkout/js/model/payment/method-converter',
    'TNW_Subscriptions/js/checkout/action/select-billing-address'
], function (
    ko,
    $,
    _,
    Component,
    $t,
    customerData,
    customer,
    addressList,
    quote,
    paymentService,
    paymentMethodConverter,
    selectBillingAddress
) {
    'use strict';

    /** Set payment methods to collection */
    paymentService.setPaymentMethods(paymentMethodConverter(window.checkoutConfig.paymentMethods));

    var newAddressOption = {
            /**
             * Get new address label
             * @returns {String}
             */
            getAddressInline: function () {
                return $t('New Address');
            },
            customerAddressId: null
        },
        countryData = customerData.get('directory-data'),
        addressOptions = addressList().filter(function (address) {
            return address.getType() === 'customer-address';
        });

    addressOptions.push(newAddressOption);

    return Component.extend({
        defaults: {
            activeMethod: '',
            selectedAddress: null,
            isAddressDetailsVisible: quote.billingAddress() != null,
            isAddressFormVisible: !customer.isLoggedIn() || addressOptions.length === 1,
            isAddressSameAsShipping: true,
            saveInAddressBook: 1
        },
        isVisible: ko.observable(true),
        quoteIsVirtual: quote.isVirtual(),
        isPaymentMethodsAvailable: ko.computed(function () {
            return paymentService.getAvailablePaymentMethods().length > 0;
        }),

        /** @inheritdoc */
        initialize: function () {
            this._super();

            return this;
        },

        /**
         * @return {exports.initObservable}
         */
        initObservable: function () {
            this._super()
                .observe([
                    'selectedAddress',
                    'isAddressDetailsVisible',
                    'isAddressFormVisible',
                    'isAddressSameAsShipping',
                    'saveInAddressBook'
                ]);

            quote.shippingAddress.subscribe(function (shippingAddress) {
                if (this.isAddressSameAsShipping()) {
                    selectBillingAddress(shippingAddress);
                }
            }, this);

            quote.shippingAddress.valueHasMutated();

            quote.billingAddress.subscribe(function (newAddress) {
                if (quote.isVirtual()) {
                    this.isAddressSameAsShipping(false);
                } else {
                    this.isAddressSameAsShipping(
                        newAddress != null &&
                        newAddress.getCacheKey() === quote.shippingAddress().getCacheKey()
                    );
                }

                if (newAddress != null && newAddress.saveInAddressBook !== undefined) {
                    this.saveInAddressBook(newAddress.saveInAddressBook);
                } else {
                    this.saveInAddressBook(1);
                }
                this.isAddressDetailsVisible(true);
            }, this);

            return this;
        },

        /**
         * @return {*}
         */
        getFormKey: function () {
            return window.checkoutConfig.formKey;
        },

        currentBillingAddress: quote.billingAddress,
        addressOptions: addressOptions,
        customerHasAddresses: addressOptions.length > 1,

        canUseShippingAddress: ko.computed(function () {
            return !quote.isVirtual() && quote.shippingAddress() && quote.shippingAddress().canUseForBilling();
        }),

        useShippingAddress: function () {
            if (this.isAddressSameAsShipping()) {
                selectBillingAddress(quote.shippingAddress());

                this.isAddressDetailsVisible(true);
            } else {
                quote.billingAddress(null);
                this.isAddressDetailsVisible(false);
            }

            return true;
        },

        /**
         * @param {Number} countryId
         * @return {*}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] !== undefined
                ? countryData()[countryId].name
                : '';
        },

        /**
         * Edit address action
         */
        editAddress: function () {
            quote.billingAddress(null);
            this.isAddressDetailsVisible(false);
        },

        /**
         * @param {Object} address
         */
        onAddressChange: function (address) {
            this.isAddressFormVisible(address === newAddressOption);
        },

        /**
         * @param {Object} address
         * @return {*}
         */
        addressOptionsText: function (address) {
            return address.getAddressInline();
        }
    });
});
