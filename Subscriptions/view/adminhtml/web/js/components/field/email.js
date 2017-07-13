/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'uiRegistry'
], function (Abstract, $j, uiRegistry) {
    'use strict';

    return Abstract.extend({
        default: {
          valueBefore: '',
            options: []
        },

        /**
         * {@inheritDoc}
         */
        hasChanged: function () {
            if (this.valueBefore != this.value()){

                var url = typeof this.imports.checkEmailUrl == "undefined"
                    ? '': this.imports.checkEmailUrl;

                var validate = this.validate();

                if (validate.valid) {
                    this.sendCheckEmailAjax(url);
                }
            }

            this.valueBefore = this.value();
        },

        sendCheckEmailAjax: function (url) {
            $j.ajax({
                showLoader: true,
                url: url,
                data: {email: this.value},
                type: "POST",
                dataType: 'json'
            }).done(function (data) {
                debugger;
                var modal = uiRegistry.get('index = customer_account_already_exists');
                modal._elems[0].openModal();
            });
        }
    });
});
