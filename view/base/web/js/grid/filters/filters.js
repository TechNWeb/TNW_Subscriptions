/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'jquery',
    'mageUtils',
], function (_, $, utils) {
    'use strict';

    return function (Filter) {
        /**
         * Removes empty properties from the provided object.
         *
         * @param {Object} data - Object to be processed.
         * @returns {Object}
         */
        function removeEmpty(data) {
            var result = utils.mapRecursive(data, utils.removeEmptyValues.bind(utils));

            return utils.mapRecursive(result, function (value) {
                return _.isString(value) ? value.trim() : value;
            });
        }

        return Filter.extend({
            /**
             * Sets filters data to the applied state.
             *
             * @returns {Filters} Chainable.
             */
            apply: function () {
                $('body').notification();
                this.set('applied', removeEmpty(this.filters));

                return this;
            }
        });
    };
});
