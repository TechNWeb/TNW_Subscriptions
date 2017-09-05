/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    ['jquery'],
    function($) {
        'use strict';

        return {
            method: 'rest',
            storeCode: window.tnwSubscriptionsCheckoutConfig.storeCode,
            version: 'V1',
            serviceUrl: ':method/:storeCode/:version',

            /**
             * Create url
             *
             * @param {String} url
             * @param {Object} params
             * @returns {String}
             */
            createUrl: function(url, params) {
                var completeUrl = this.serviceUrl + url;

                return this._bindParams(completeUrl, params);
            },

            /**
             * Bind params
             *
             * @param {String} url
             * @param {Object} params
             * @returns {String}
             * @private
             */
            _bindParams: function(url, params) {
                var urlParts = url.split('/').filter(Boolean);

                params.method = this.method;
                params.storeCode = this.storeCode;
                params.version = this.version;

                $.each(urlParts, function(key, part) {
                    part = part.replace(':', '');
                    if (params[part] != undefined) {
                        urlParts[key] = params[part];
                    }
                });

                return urlParts.join('/');
            }
        };
    }
);
