/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mageUtils',
    'jquery/jquery-storageapi'
], function ($, storage, utils) {
    'use strict';

    var cacheKey = 'checkout-data',

        /**
         * @param {Object} data
         */
        saveData = function (data) {
            storage.set(cacheKey, data);
        },

        /**
         * @return {*}
         */
        initData = function () {
            return {
                'forceLoginValue': null
            };
        },

        /**
         * @return {*}
         */
        getData = function () {
            var data = storage.get(cacheKey)();

            if ($.isEmptyObject(data)) {
                data = $.initNamespaceStorage('mage-cache-storage').localStorage.get(cacheKey);

                if ($.isEmptyObject(data)) {
                    data = initData();
                    saveData(data);
                }
            }

            return data;
        };

    return function (originalCheckoutData) {

        /**
         * Pulling the force login value from persistence storage
         *
         * @return {*}
         */
        originalCheckoutData.getForceLoginValue = function () {
            var obj = getData();

            return obj.forceLoginValue ? obj.forceLoginValue : false;
        };

        /**
         * Setting the force login value pulled from persistence storage
         *
         * @param {Boolean} status
         */
        originalCheckoutData.setForceLoginValue = function (status) {
            var obj = getData();

            obj.forceLoginValue = status;
            saveData(obj);
        }

        return originalCheckoutData;
    };
});
