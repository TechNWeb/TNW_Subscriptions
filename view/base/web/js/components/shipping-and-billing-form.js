define([
    'Magento_Ui/js/form/form'
], function (Form) {
    return Form.extend({

        /**
         * Save shipping&billing form after successful payment gateway process.
         * @param responseData
         */
        onPaymentFormResponse: function (responseData) {
            if (!responseData.error) {
                this.save();
            }
        }
    })
})
