/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(['underscore', 'mageUtils'], function (_, utils) {
    'use strict';

    return {
        validationErrors: [],

        /**
         * @return {Object}
         */
        getRules: function () {
            return {
                'country_id': {
                    'required': true
                }
            };
        },

        /**
         * @param {Object} address
         * @return {Boolean}
         */
        validate: function (address) {
            var self = this;

            this.validationErrors = [];
            _.each(this.getRules(), function (rule, field) {
                var message;

                if (rule.required && utils.isEmpty(address[field])) {
                    message = $t('Field ') + field + $t(' is required.');

                    self.validationErrors.push(message);
                }
            });

            return !this.validationErrors.length;
        }
    };
});
