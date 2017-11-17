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
        'mage/translate'
    ],
    function ($, Component, registry, _) {
        'use strict';

        return Component.extend({

            beforeSubmit: function () {
                var current = this,
                    needShowRequiredError = true,
                    validForm = true;

                this.validate();
                if (this.source.params.invalid){
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

            triggerSave:function (errors) {
                var current = this;
                _.each(this.source.data.payment, function (fields, code) {
                    if (fields.method === "1"){
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
            }
        });
    }
);
