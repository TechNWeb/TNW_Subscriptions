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
                paymentContainer: '',
                listens: {
                    responseData: 'processResponseData'
                },
                modules: {
                    formRight: 'name = cart.steps.insert_form_right'
                }
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

            /**
             * Sends request to payment gateway.
             *
             * @returns {boolean}
             */
            beforeSubmit: function () {
                var needShowRequiredError = true,
                    validForm = true,
                    fieldset = null;

                this.validate();
                if (this.source.params.invalid) {
                    return false;
                }
                _.each(this.source.data.payment, function (fields, code) {
                    if (fields.method === "1") {
                        fieldset = registry.get('index = ' + code);
                        fieldset.beforeSubmit();
                        validForm = _.isArray(fieldset.payment_errors()) && fieldset.payment_errors().length === 0;
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

                if (validForm) {
                    this.updateButtons(true);
                }

                return validForm;
            },

            /**
             * Saves payment step form after response from gateways.
             *
             * @param {array} errors
             */
            triggerSave: function (errors) {
                var current = this;
                _.each(this.source.data.payment, function (fields, code) {
                    if (fields.method === "1") {
                        if (errors && errors.length > 0) {
                            var fieldset = registry.get('index = ' + code);
                            fieldset.processErrors(errors);
                            current.updateButtons(false);
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
             * Enables/disables checkout buttons.
             *
             * @param {bool} flag
             */
            updateButtons:function (flag) {
                var button = registry.get('index = next_step'),
                    bottomButton = registry.get('index = bottom_next_step');

                if (button) {
                    button.disabled(flag);
                }

                if (bottomButton) {
                    bottomButton.disabled(flag);
                }
            },

            /**
             * Processes response data.
             *
             * @param {Object} data
             */
            processResponseData: function (data) {
                if (data.error) {
                    this.updateButtons(false);
                    this.formRight().updateData();
                }
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
                    var minMonth = parseInt(this.source[paymentCode + '_start_on_month']) + 1;
                    var minYear = parseInt(this.source[paymentCode + '_start_on_year']);
                    if (minMonth > 12) {
                        minMonth = 1;
                        minYear = minYear +1;
                    }
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
