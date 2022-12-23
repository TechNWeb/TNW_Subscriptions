define([
    'jquery',
    'Magento_Ui/js/form/form',
    'uiRegistry',
    'mage/translate'
], function ($, Form, registry) {
    return Form.extend({

        defaults: {
            processUrl: null,
            processTime: null
        },

        /**
         * Save shipping&billing form after successful payment gateway process.
         * @param responseData
         */
        onPaymentFormResponse: function (responseData) {
            if (!responseData.error) {
                if (this.source.data.shipping_method !== "") {
                    this.save();
                } else {
                    var firstFieldSet = registry.get('index = shipping_methods');
                    if (firstFieldSet !== undefined) {
                        if (!firstFieldSet.visible()) {
                            this.save();
                        } else {
                            firstFieldSet.resetErrors();
                            firstFieldSet.set('shipping_errors', [$.mage.__('Please select shipping.')]);
                        }
                    }
                }
            }
        },

        process: function () {
            var postData = (typeof FORM_KEY !== 'undefined') ? {'form_key': FORM_KEY} : {},
                self = this;
            postData.shipping_method = this.source.data.shipping_method;

            if (!postData.shipping_method) {
                return
            }
            $.ajax({
                url: this.processUrl,
                type: 'post',
                context: this,
                data: postData,
                success: function (response) {
                    if (response.error) {
                        registry.get('index = shipping_methods').set('shipping_errors', [response.error_messages])
                    }
                    $('body').trigger('processStop');
                    self.processTime(Date.now());
                },
                complete: function () {
                    $('body').trigger('processStop');
                }
            });
        },

        initObservable: function () {
            return this._super().observe(['processTime']);
        }
    })
})
