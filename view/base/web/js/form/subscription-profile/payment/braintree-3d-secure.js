/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'braintreeThreeDSecure',
], function ($, $t, threeDSecure) {
    'use strict';

    return {
        config: null,

        /**
         * Set 3d secure config
         * @param {Object} config
         */
        setConfig: function (config) {
            this.config = config;
            this.config.totalAmount = parseFloat(config.totalAmount).toFixed(2);
            this.config.thresholdAmount = parseFloat(config.thresholdAmount);
        },

        /**
         * Validate Braintree payment nonce
         * @param {Object} context
         * @returns {Object}
         */
        validate: function (context) {
            var clientInstance = context.braintreeClientInstance,
                state = $.Deferred(),
                totalAmount = this.config.totalAmount,
                thresholdAmount = this.config.thresholdAmount,
                countryId = $('#country').val();

            if ((countryId && !this.isCountryAvailable(countryId)) || totalAmount < thresholdAmount) {
                state.resolve();
                return state.promise();
            }

            var setup3d = function(clientInstance) {
                threeDSecure.create({
                    version: 2,
                    client: clientInstance
                }, function (threeDSecureErr, threeDSecureInstance) {
                    if (threeDSecureErr) {
                        return state.reject($t('Please try again with another form of payment.'));
                    }

                    var threeDSContainer = document.createElement('div'),
                        tdmask = document.createElement('div'),
                        tdframe = document.createElement('div'),
                        tdbody = document.createElement('div');

                    threeDSContainer.id = 'braintree-three-d-modal';
                    tdmask.className ="bt-mask";
                    tdframe.className ="bt-modal-frame";
                    tdbody.className ="bt-modal-body";

                    tdframe.appendChild(tdbody);
                    threeDSContainer.appendChild(tdmask);
                    threeDSContainer.appendChild(tdframe);

                    threeDSecureInstance.verifyCard({
                        amount: totalAmount,
                        nonce: context.paymentMethodNonce,
                        onLookupComplete: function (data, next) {
                            next();
                        },
                        addFrame: function (err, iframe) {
                            if (err) {
                                console.log("Unable to verify card over 3D Secure", err);
                                return state.reject($t('Please try again with another form of payment.'));
                            }

                            tdbody.appendChild(iframe);
                            document.body.appendChild(threeDSContainer);
                        },
                        removeFrame: function () {
                            document.body.removeChild(threeDSContainer);
                        }
                    }, function (err, response) {
                        if (err) {
                            console.error("3dsecure validation failed", err);
                            return state.reject($t('Please try again with another form of payment.'));
                        }

                        var liability = {
                            shifted: response.liabilityShifted,
                            shiftPossible: response.liabilityShiftPossible
                        };

                        if (liability.shifted || !liability.shifted && !liability.shiftPossible) {
                            context.paymentMethodNonce = response.nonce;
                            state.resolve(response.nonce);
                        } else {
                            state.reject($t('Please try again with another form of payment.'));
                        }
                    });
                });
            };
            setup3d(clientInstance);

            return state.promise();
        },

        /**
         * Check if current country is available for 3d secure
         * @param {String} countryId
         * @returns {Boolean}
         */
        isCountryAvailable: function (countryId) {
            var key,
                specificCountries = this.config.specificCountries;

            // all countries are available
            if (!specificCountries.length) {
                return true;
            }

            for (key in specificCountries) {
                if (countryId === specificCountries[key]) {
                    return true;
                }
            }

            return false;
        }
    };
});
