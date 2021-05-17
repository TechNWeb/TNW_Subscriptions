define([
    // 'Magento_Ui/js/form/form',
    'TNW_Subscriptions/js/components/payment-form',
    'jquery',
    'uiRegistry',
    'underscore',
    'mage/translate'
], function (uiForm, $, uiRegistry, _, $t) {
    return uiForm.extend({

        initialize: function () {
            var self = this;
            this._super();
            uiRegistry.async('ns = ' + this.ns + ', index = step-wizard')(function (wizard) {
                self.stepWizard = wizard;
            });
            window.FORM_KEY = $('[name=form_key]').val();
        },

        startMassEdit: function () {
            var selectionsColumn = uiRegistry.get('ns = tnw_subscriptionprofile_account_index, index = ids');

            this.source.set('data.selectedSubsIds', selectionsColumn.getSelections().selected);
            uiRegistry.get('ns = ' + this.ns + ',index = wizard_modal').openModal();
        },

        submitShippingAddress: function () {
            var shippingMethodRadio = uiRegistry.get('ns =' + this.ns + ', dataScope = data.shipping_method'),
                postData = {
                    isAjax: true,
                    form_key: window.FORM_KEY,
                    shipping_address: this.source.get('data.shipping_address'),
                    isNewShippingAddress: uiRegistry.get('ns =' + this.ns + ',index = new_shipping_address')
                        .visible(),
                    selectedSubsIds: this.source.get('data.selectedSubsIds'),
                    region: this.source.get('data.region'),
                };

            $('body').trigger('processStart');

            $.ajax({
                url: this.update_shipping_url,
                type: 'post',
                async: true,
                context: this,
                data: postData,
                dataType: 'json',
            }).done(function (response) {
                if (!response.error && response.shipping_methods && response.shipping_address_summary) {
                    shippingMethodRadio.options(response.shipping_methods);
                    shippingMethodRadio.visible(true);
                    this.source.set('data.summary.shipping_address', response.shipping_address_summary);
                    this.stepWizard.selectedStep(this.stepWizard.wizard.next());
                    shippingMethodRadio.disabled(false);
                } else if (response.error) {
                    this.stepWizard.wizard.setNotificationMessage(response.error, true);
                }
            }).fail(function () {
                this.stepWizard.wizard.setNotificationMessage($t('Something went wrong.'), true);
            }).always(function () {
                $('body').trigger('processStop')
            });
        },

        skipShipping: function () {
            this.source.set('data.skip_shipping', true);
            this.source.set('data.billing_address.same_as_shipping', '0');
            this.stepWizard.showSpecificStepByName(this.stepWizard.name + '.billing_address');
        },

        skipPayment: function () {
            this.source.set('data.skip_payment', true);
            this.stepWizard.showSpecificStepByName(this.stepWizard.name + '.summary');
        },

        processResponseData: function (data) {
            if (data.error && !(data.mass_edit_result && data.mass_edit_result.error)) {
                var message = '';

                if (data.error_messages && data.error_messages.length) {
                    data.error_messages.forEach(function (msg) {
                        message += msg + ' ';
                    });
                } else if (data.messages) {
                    message = data.messages;
                }

                this.stepWizard.wizard.setNotificationMessage(message, true);
                $('body').trigger('processStop');
                return false;
            }
            if (data.payment_summary && data.billing_address_summary && data.payment) {
                this.source.set('data.summary.payment_method', data.payment_summary);
                this.source.set('data.summary.billing_address', data.billing_address_summary);
                this.source.set('data.summary.payment_info', data.payment);
            }
            if (data.mass_edit_result
                && data.mass_edit_result.message
                && this.stepWizard.selectedStep() === _.last(this.stepWizard.stepsNames)
            ) {
                if (!data.mass_edit_result.error) {
                    this.stepWizard.wizard.setNotificationMessage(data.mass_edit_result.message);
                    this.stepWizard.disabled(true);
                    $('#messages .message').addClass('success');
                } else {
                    this.stepWizard.wizard.setNotificationMessage(data.mass_edit_result.message, true);
                }
                return false;
            }
            this.stepWizard.selectedStep(this.stepWizard.wizard.next());
            $('body').trigger('processStop');
        }
    })
})
