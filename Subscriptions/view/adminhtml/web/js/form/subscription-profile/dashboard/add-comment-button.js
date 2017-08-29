/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/components/button',
    'jquery',
], function (_, Button, $) {
    'use strict';

    return Button.extend({
        defaults: {
            displayPrimary: true
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
            var url = typeof this.imports.url == "undefined" ? '' : this.imports.url;
            var comment = $("textarea[name='dashboard[comment_area]']").val();

            this.sendAjaxAddComment(url, comment)
        },

        /**
         * Send ajax and assign subscriptions to existing customer.
         *
         * @param url
         * @param comment
         */
        sendAjaxAddComment: function (url, comment) {
            $.ajax({
                showLoader: true,
                url: url,
                data: {
                    form_key: window.FORM_KEY,
                    comment: comment
                },
                type: "POST",
                dataType: 'json'
            }).done();
        }
    });
});
