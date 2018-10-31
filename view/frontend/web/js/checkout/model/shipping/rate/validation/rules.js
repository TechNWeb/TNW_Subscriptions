/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(['jquery'], function ($) {
    'use strict';

    var ratesRules = [];

    return {
        /**
         * @param {Object} rule
         */
        registerRules: function (rule) {
            ratesRules.push(rule);
        },

        /**
         * @param {Object} address
         * @return {Boolean}
         */
        validateAddressData: function (address) {
            return !ratesRules.some(function (rule) {
                return rule.validate(address) === false
            });
        },

        /**
         * @return {Array}
         */
        getRules: function () {
            return ratesRules;
        },

        /**
         * @return {Array}
         */
        getObservableFields: function () {
            var observableFields = [];

            $.each(this.getRules(), function (index, rule) {
                $.each(rule.getRules(), function (field) {
                    if (observableFields.indexOf(field) === -1) {
                        observableFields.push(field);
                    }
                });
            });

            return observableFields;
        }
    };
});
