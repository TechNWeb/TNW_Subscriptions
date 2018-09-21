/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
        defaults: {
            displayPrimary: true,
            subButtonLeft: false,
            subButtonRight: true
        },

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe([
                    'disabled',
                    'displayPrimary',
                    'subButtonLeft',
                    'subButtonRight'
                ]);
        },

        /**
         * @inheritdoc
         */
        action: function () {
            var options = uiRegistry.get('index=customer_exists_options');
            var valid = options.validate();

            if (valid.valid) {
                if (options.value() === 'link') {
                    var url = typeof this.imports.url == "undefined"
                        ? '' : this.imports.url;

                    if (url) {
                        this.sendAjaxAssignSubscriptionsToCustomer(url);
                    }
                } else {
                    var formName = 'tnw_subscriptionprofile_create_account_form';
                    var modalName = 'customer_account_already_exists_customerModal';
                    $j('.' + formName + '_' + formName + '_' + modalName + ' .modal-header .action-close').click();
                }
            }
        },

        /**
         * Send ajax and assign subscriptions to existing customer.
         *
         * @param url
         */
        sendAjaxAssignSubscriptionsToCustomer: function (url) {
            $j.ajax({
                showLoader: true,
                url: url,
                data:{form_key: window.FORM_KEY},
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
