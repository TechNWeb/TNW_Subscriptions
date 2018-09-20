/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

/**
 * Checkout adapter for customer data storage
 */
define([
    'underscore',
    'Magento_Customer/js/model/address-list',
    'TNW_Subscriptions/js/checkout/data',
    'TNW_Subscriptions/js/checkout/model/quote',
    'TNW_Subscriptions/js/checkout/action/create-shipping-address',
    'TNW_Subscriptions/js/checkout/action/select-shipping-method',
    'TNW_Subscriptions/js/checkout/action/select-shipping-address',
    'TNW_Subscriptions/js/checkout/model/address-converter',
    'TNW_Subscriptions/js/checkout/action/create-billing-address',
    'TNW_Subscriptions/js/checkout/action/select-payment-method',
    'TNW_Subscriptions/js/checkout/action/select-billing-address'
], function (
    _,
    addressList,
    data,
    quote,
    createShippingAddress,
    selectShippingMethod,
    selectShippingAddress,
    addressConverter,
    createBillingAddress,
    selectPaymentMethod,
    selectBillingAddress
) {
    'use strict';

    return {

        /**
         * Resolve estimation address. Used local storage
         */
        resolveEstimationAddress: function () {
            var address;

            if (data.getShippingAddressFromData()) {
                address = addressConverter.formAddressDataToQuoteAddress(data.getShippingAddressFromData());
                selectShippingAddress(address);
            } else {
                this.resolveShippingAddress();
            }

            if (quote.isVirtual()) {
                if (data.getBillingAddressFromData()) {
                    address = addressConverter.formAddressDataToQuoteAddress(
                        data.getBillingAddressFromData()
                    );
                    selectBillingAddress(address);
                } else {
                    this.resolveBillingAddress();
                }
            }
        },

        /**
         * Resolve shipping address. Used local storage
         */
        resolveShippingAddress: function () {
            var newCustomerShippingAddress = data.getNewCustomerShippingAddress();

            if (newCustomerShippingAddress) {
                createShippingAddress(newCustomerShippingAddress);
            }

            this.applyShippingAddress();
        },

        /**
         * Apply resolved estimated address to quote
         *
         * @param {Object} isEstimatedAddress
         */
        applyShippingAddress: function (isEstimatedAddress) {
            var address,
                shippingAddress,
                isConvertAddress,
                addressData,
                isShippingAddressInitialized;

            if (addressList().length === 0) {
                address = addressConverter.formAddressDataToQuoteAddress(
                    data.getShippingAddressFromData()
                );
                selectShippingAddress(address);
            }

            shippingAddress = quote.shippingAddress();
            isConvertAddress = isEstimatedAddress || false;

            if (!shippingAddress) {
                isShippingAddressInitialized = addressList.some(function (addressFromList) {
                    if (data.getSelectedShippingAddress() === addressFromList.getKey()) {
                        addressData = isConvertAddress
                            ? addressConverter.addressToEstimationAddress(addressFromList)
                            : addressFromList;
                        selectShippingAddress(addressData);

                        return true;
                    }

                    return false;
                });

                if (!isShippingAddressInitialized) {
                    isShippingAddressInitialized = addressList.some(function (addrs) {
                        if (addrs.isDefaultShipping()) {
                            addressData = isConvertAddress
                                ? addressConverter.addressToEstimationAddress(addrs)
                                : addrs;
                            selectShippingAddress(addressData);

                            return true;
                        }

                        return false;
                    });
                }

                if (!isShippingAddressInitialized && addressList().length === 1) {
                    addressData = isConvertAddress
                        ? addressConverter.addressToEstimationAddress(addressList()[0])
                        : addressList()[0];
                    selectShippingAddress(addressData);
                }
            }
        },

        /**
         * @param {Object} ratesData
         */
        resolveShippingRates: function (ratesData) {
            var selectedShippingRate = data.getSelectedShippingRate(),
                availableRate = false;

            if (ratesData.length === 1) {
                //set shipping rate if we have only one available shipping rate
                selectShippingMethod(ratesData[0]);

                return;
            }

            if (quote.shippingMethod()) {
                availableRate = _.find(ratesData, function (rate) {
                    return rate['carrier_code'] === quote.shippingMethod()['carrier_code'] &&
                        rate['method_code'] === quote.shippingMethod()['method_code'];
                });
            }

            if (!availableRate && selectedShippingRate) {
                availableRate = _.find(ratesData, function (rate) {
                    return rate['carrier_code'] + '_' + rate['method_code'] === selectedShippingRate;
                });
            }

            if (!availableRate && window.checkoutConfig.selectedShippingMethod) {
                availableRate = window.checkoutConfig.selectedShippingMethod;
                selectShippingMethod(window.checkoutConfig.selectedShippingMethod);

                return;
            }

            //Unset selected shipping method if not available
            if (!availableRate) {
                selectShippingMethod(null);
            } else {
                selectShippingMethod(availableRate);
            }
        },

        /**
         * Resolve payment method. Used local storage
         */
        resolvePaymentMethod: function () {
            var availablePaymentMethods = paymentService.getAvailablePaymentMethods(),
                selectedPaymentMethod = data.getSelectedPaymentMethod();

            if (selectedPaymentMethod) {
                availablePaymentMethods.some(function (payment) {
                    if (payment.method === selectedPaymentMethod) {
                        selectPaymentMethod(payment);
                    }
                });
            }
        },

        /**
         * Resolve billing address. Used local storage
         */
        resolveBillingAddress: function () {
            var selectedBillingAddress = data.getSelectedBillingAddress(),
                newCustomerBillingAddressData = data.getNewCustomerBillingAddress();

            if (selectedBillingAddress) {
                if (selectedBillingAddress === 'new-customer-address' && newCustomerBillingAddressData) {
                    selectBillingAddress(createBillingAddress(newCustomerBillingAddressData));
                } else {
                    addressList.some(function (address) {
                        if (selectedBillingAddress === address.getKey()) {
                            selectBillingAddress(address);
                        }
                    });
                }
            } else {
                this.applyBillingAddress();
            }
        },

        /**
         * Apply resolved billing address to quote
         */
        applyBillingAddress: function () {
            var shippingAddress;

            if (quote.billingAddress()) {
                selectBillingAddress(quote.billingAddress());

                return;
            }
            shippingAddress = quote.shippingAddress();

            if (shippingAddress &&
                shippingAddress.canUseForBilling() &&
                (shippingAddress.isDefaultShipping() || !quote.isVirtual())
            ) {
                //set billing address same as shipping by default if it is not empty
                selectBillingAddress(quote.shippingAddress());
            }
        }
    };
});
