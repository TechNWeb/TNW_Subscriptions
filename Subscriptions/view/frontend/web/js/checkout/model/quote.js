/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([], function () {
    'use strict';

    /**
     * Returns new quote object.
     *
     * @param {Object} addressData
     * @return {Object}
     */
    return function (quoteData) {
        return {
            data: quoteData,

            /**
             * @return {*}
             */
            getQuoteId: function () {
                return quoteData['entity_id'];
            },

            /**
             * @return {Boolean}
             */
            isVirtual: function () {
                return !!Number(quoteData['is_virtual']);
            },

            /**
             * @return {Array}
             */
            getItems: function () {
                return quoteData.items;
            },

            /**
             * @return {String}
             */
            getProfileName: function () {
                return quoteData['profile_name'];
            },

            /**
             * @return {String}
             */
            getProfileDescription: function () {
                return quoteData['profile_description'];
            }
        };
    };
});
