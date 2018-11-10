/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'uiElement'
], function (Element) {
    'use strict';

    return Element.extend({
        defaults: {
            template: 'TNW_Subscriptions/component/container-with-message',
            messages: []
        },

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe([
                    'messages'
                ]);
        },

        /**
         * Check if need show messages block.
         *
         * @returns {boolean}
         */
        canShowMessage: function () {
            return this.getMessageData().filter().length > 0;
        },

        /**
         * Setting message.
         *
         * @param message
         */
        setMessagesData: function (message) {
            var currentMessages = this.getMessageData();
            if (currentMessages.indexOf(message) < 0) {
                this.messages.push(message);
            }
        },

        /**
         * Return messages array.
         *
         * @returns {Array}
         */
        getMessageData: function () {
            return this.messages;
        },
    });
});
