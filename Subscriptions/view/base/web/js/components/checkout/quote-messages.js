/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define(
    [
        'jquery',
        'TNW_Subscriptions/js/components/subscriptions-form',
        'uiRegistry',
        'Magento_Ui/js/model/messageList',
        'underscore'
    ],
    function ($, Component, registry, messageList, _) {
        'use strict';

        return Component.extend({
            initialize: function () {
                this._super()
                    .renderMessages();

                return this;
            },

            /**
             * Render messages.
             */
            renderMessages: function () {
                var messages = this.source.messages;
                messageList.clear();
                debugger;
                if (!_.isEmpty(messages['error'])) {
                    _.each(messages['error'], function (message) {
                        messageList.getErrorMessages().push(message);
                    });
                }
                if (!_.isEmpty(messages['success'])) {
                    _.each(messages['success'], function (message) {
                        messageList.getSuccessMessages().push(message);
                    });
                }
                if (messages.needReload) {
                    setTimeout(this.reloadPage, 5000);
                }
            },

            reloadPage: function () {
                location.reload();
            }
        });
    }
);
