/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/components/button',
    'jquery',
    'uiRegistry'
], function (_, Button, $j, uiRegistry) {
    'use strict';

    return Button.extend({

        /**
         * @inheritdoc
         */
        action: function () {
            var options = uiRegistry.get('index=cancel_button_popup_options');
            var valid = options.validate();

            if (valid.valid) {
                var url = typeof this.imports.url == "undefined"
                    ? '' : this.imports.url;

                if (url) {
                    this.sendAjax(url, options.value());
                }
            }
        },

        /**
         * Send ajax to cancel subscription.
         *
         * @param url
         */
        sendAjax: function (url, value) {
            $j.ajax({
                showLoader: true,
                url: url,
                data:
                    {
                        form_key: window.FORM_KEY,
                        value: value
                    },
                type: "POST",
                dataType: 'json'
            }).done(function (data) {
                if (data.result == true) {
                    location.reload();
                }
            });
        }
    });
});
