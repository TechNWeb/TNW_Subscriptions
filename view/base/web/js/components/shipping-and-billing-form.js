define([
    'jquery',
    'Magento_Ui/js/form/form',
    'uiRegistry',
    'mage/translate'
], function ($, Form, registry) {
    return Form.extend({

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
                        firstFieldSet.resetErrors();
                        firstFieldSet.set('shipping_errors', [$.mage.__('Please select shipping.')]);
                    }
                }
            }
        }
    })
})
