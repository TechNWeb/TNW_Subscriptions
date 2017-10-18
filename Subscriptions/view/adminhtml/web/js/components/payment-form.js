define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        'uiRegistry',
        'underscore',
        'mage/translate'
    ],
    function ($, Component, registry, _) {
        'use strict';

        return Component.extend({

            beforeSubmit: function () {
                this.validate();
                if (this.source.params.invalid){
                    return;
                }

                var current = this,
                    needShowRequiredError = false;

                _.each(this.source.data.payment, function (fields, code) {
                    if (fields.method === "1") {
                        switch (code) {
                            case 'payflowpro':
                                registry.get('index = ' + code).beforeSubmit();
                                break;
                            case 'checkmo':
                            default:
                                current.save();
                        }
                        needShowRequiredError = false;
                    } else {
                        needShowRequiredError = true;
                    }
                });

                if (needShowRequiredError) {
                    var firstFieldSet = registry.get('index = payment_information').elems()[0];
                    if (firstFieldSet !== undefined) {
                        firstFieldSet.resetErrors();
                        firstFieldSet.set('payment_errors', [$.mage.__('Please select payment.')]);
                    }
                }
            },

            triggerSave:function (errors) {
                var current = this;
                _.each(this.source.data.payment, function (fields, code) {
                    if (fields.method === "1"){
                        if (errors && errors.length > 0){
                            var fieldset = registry.get('index = ' + code);
                            fieldset.processErrors(errors);
                            return;
                        }
                        switch (code) {
                            case 'payflowpro':
                                if (fields.additional.cc_number){
                                    fields.additional.cc_last_4 = fields.additional.cc_number.substr(-4);
                                    delete fields.additional.cc_number;
                                }
                                delete fields.additional.cc_cid;
                                break;
                        }
                        current.save();
                    }
                });
            }
        });
    }
);
