/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Catalog/js/price-utils',
    'jquery'
], function (priceUtils, $) {
    'use strict';

    var priceFormat = {
        requiredPrecision: 2,
        integerRequired: 1,
        decimalSymbol: '.',
        groupSymbol: ',',
        groupLength: ','
    };

    return {
        formatPrice: formatPrice,
        formatToNumber: formatToNumber
    };

    /**
     * Return locale formatted price amount.
     *
     * @param amount
     * @param format
     * @returns {*}
     */
    function formatPrice(amount, format) {
        if (typeof format == 'undefined') {
            format = priceFormat;
        } else if (typeof format == 'string') {
            format = $.parseJSON(format);
        }
        if (typeof amount === 'string') {
            var decimalSymbol = format.decimalSymbol === undefined ? ',' : format.decimalSymbol,
                groupSymbol = format.groupSymbol === undefined ? ',' : format.groupSymbol,
                groupRegEx = new RegExp(groupSymbol, 'g');
            amount = parseFloat(amount.replace(decimalSymbol, '.').replace(groupRegEx, ''));
        }
        return priceUtils.formatPrice(amount, format);
    }

    /**
     * Return Price formatted to number.
     *
     * @param value
     * @param format
     * @returns {*}
     */
    function formatToNumber(value, format) {
        var precision = isNaN(format.requiredPrecision = Math.abs(format.requiredPrecision)) ? 2 : format.requiredPrecision;
        var decimalSymbol = format.decimalSymbol === undefined ? ',' : format.decimalSymbol;
        var groupLength = format.groupLength === undefined ? 3 : format.groupLength;
        var groupSymbol = format.groupSymbol === undefined ? '.' : format.groupSymbol;
        var fraction = '00';
        precision = precision * (-1);
        var returnNumber = value;
        if (typeof value == 'number') {
            value = value.toString();
        }
        var indexOfDecimalSymbol = value.indexOf(decimalSymbol);

        if (indexOfDecimalSymbol >= 0) {
            var indexToSlice = (value.length - indexOfDecimalSymbol - 1) * (-1);
            fraction = value.slice(indexToSlice);
            var wholeNumber = value.split(decimalSymbol + fraction);
            returnNumber = wholeNumber[0];
        }

        if (returnNumber.length > groupLength) {
            returnNumber = returnNumber.split(groupSymbol).join('');
        }

        returnNumber = returnNumber + '.' + fraction;

        return returnNumber;
    }
});
