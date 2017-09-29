/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/button',
    'jquery',
    'uiRegistry'
], function (Button, $, registry) {
    'use strict';

    return Button.extend({

        defaults: {
            saveUrl: ''
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
            $.ajax({
                showLoader: true,
                url: this.saveUrl,
                data: $('.additional-attributes-field-set fieldset').serialize()
                    + "&form_key=" + window.FORM_KEY
                    + "&subscription_profile_id=" + $('.additional-attributes-field-set input[name="additional_attributes[subscription_profile_id]"]').val(),
                type: "POST",
                dataType: 'json'
            }).done(function (data) {

            })
        }
    });
});
