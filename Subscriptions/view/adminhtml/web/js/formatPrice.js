/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Catalog/js/price-utils'
], function (priceUtils) {
    'use strict';

    var priceFormat = {
        requiredPrecision: 2,
        integerRequired: 1,
        decimalSymbol: '.',
        groupSymbol: ',',
        groupLength: ','
    };

    return {
        formatPrice: formatPrice
    };

    function formatPrice(amount) {
        return priceUtils.formatPrice(amount, priceFormat);
    }
});
