/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Customer/js/model/address-list',
    'TNW_Subscriptions/js/checkout/model/address-converter'
], function (addressList, addressConverter) {
    'use strict';

    return function (addressData) {
        var address = addressConverter.formAddressDataToQuoteAddress(addressData),
            isAddressUpdated = addressList().some(function (currentAddress, index, addresses) {
                if (currentAddress.getKey() == address.getKey()) {
                    addresses[index] = address;

                    return true;
                }

                return false;
            });

        if (!isAddressUpdated) {
            addressList.push(address);
        } else {
            addressList.valueHasMutated();
        }

        return address;
    };
});
