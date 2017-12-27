/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'TNW_Subscriptions/js/components/subscriptions-form',
        'uiRegistry',
        'underscore',
        'Magento_Ui/js/lib/validation/validator',
        'mage/translate'
    ],
    function ($, Component, registry, _, validator) {
        'use strict';

        return Component.extend({
            defaults: {
                addPaymentValidation: false,
                paymentContainer: ''
            },

            /** @inheritdoc */
            initialize: function () {
                this._super();

                if (this.addPaymentValidation) {
                    var current = this;
                    validator.addRule(
                        'subscription-validate-cc-exp-month',
                        function (value, rule) {
                            return current.validateExpDate(value, false, rule);
                        },
                        ''
                    );
                    validator.addRule(
                        'subscription-validate-cc-exp-year',
                        function (value, rule) {
                            return current.validateExpDate(false, value, rule);
                        },
                        ''
                    );
                }

                return this;
            },

            beforeSubmit: function () {
                var current = this,
                    needShowRequiredError = true,
                    validForm = true;

                this.validate();
                if (this.source.params.invalid) {
                    validForm = false;
                    return validForm;
                }


                _.each(this.source.data.payment, function (fields, code) {
                    if (fields.method === "1") {
                        switch (code) {
                            case 'payflowpro':
                            case 'braintree':
                                registry.get('index = ' + code).beforeSubmit();
                                break;

                            case 'checkmo':
                            default:
                                current.save();
                        }
                        needShowRequiredError = false;
                    }
                });

                if (needShowRequiredError) {
                    var firstFieldSet = registry.get('index = payment_information');
                    if (firstFieldSet !== undefined) {
                        firstFieldSet.resetErrors();
                        firstFieldSet.set('payment_errors', [$.mage.__('Please select payment.')]);
                        validForm = false;
                    }
                }

                return validForm;
            },

            triggerSave: function (errors) {
                var current = this;
                _.each(this.source.data.payment, function (fields, code) {
                    if (fields.method === "1") {
                        if (errors && errors.length > 0) {
                            var fieldset = registry.get('index = ' + code);
                            fieldset.processErrors(errors);
                            current.hideLoader();
                            return;
                        }
                        switch (code) {
                            case 'payflowpro':
                                if (fields.additional.cc_number) {
                                    fields.additional.cc_last_4 = fields.additional.cc_number.substr(-4);
                                    delete fields.additional.cc_number;
                                }
                                delete fields.additional.cc_cid;
                                break;
                        }
                        current.save();
                        current.hideLoader();
                    }
                });
            },

            /**
             * Validates card expiration date.
             *
             * @param {int} monthValue
             * @param {int} yearValue
             * @param {string} paymentCode
             * @returns {boolean}
             */
            validateExpDate: function (monthValue, yearValue, paymentCode) {
                var flag = false,
                    containerName = this.getPaymentContainerName(paymentCode),
                    container = registry.get(containerName),
                    monthComponent = registry.get(containerName + '.exp_date_month'),
                    yearComponent = registry.get(containerName + '.exp_date_year');

                if (!monthValue) {
                    monthValue = monthComponent.value();
                }
                if (!yearValue) {
                    yearValue = yearComponent.value();
                }
                if (monthValue && yearValue) {
                    var minMonth = this.source[paymentCode + '_start_on_month'];
                    var minYear = this.source[paymentCode + '_start_on_year'];
                    var isValid = this.source.params.invalid;
                    container.error(true);
                    container.groupError($.mage.__('Incorrect credit card expiration date.'));
                    if ((yearValue > minYear) || (monthValue >= minMonth && yearValue == minYear)) {
                        container.error(false);
                        container.groupError('');
                        flag = true;

                    }
                    this.source.params.invalid = isValid && flag;
                }

                return flag;
            },

            /**
             * Returns payment container full name.
             *
             * @param {string} paymentCode
             * @returns {string}
             */
            getPaymentContainerName: function (paymentCode) {
                var paymentContainerPart = '';
                if (this.paymentContainer) {
                    paymentContainerPart = '.' + this.paymentContainer;
                }
                return this.name + paymentContainerPart + ".payment_information."
                    + paymentCode + ".additional_fields.exp_date_container"
            }
        });
    }
);
