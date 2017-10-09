/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/area'
], function (Area) {
    'use strict';

    return Area.extend({
        defaults: {
            messagesData: [],
        },

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe([
                    'messagesData'
                ]);
        },

        /**
         * Check if need show messages block.
         *
         * @returns {boolean}
         */
        canShowMessage: function () {
            var currentTabFieldset = this.getCurrentFieldset();

            var flag = (typeof currentTabFieldset.tabMessages !== 'undefined');
            if (flag) {
                this.initMessagesData(currentTabFieldset.tabMessages);
            }

            return flag;
        },

        /**
         * Retrieve current fieldset element.
         *
         * @returns {*|Object}
         */
        getCurrentFieldset: function () {
            return this.getChild(this.index);
        },

        /**
         * Initialize messages to current tab.
         *
         * @param messages
         */
        initMessagesData: function (messages) {
            var current = this;
            messages.forEach(function (value) {
                current.setMessagesData(value);
            });
        },

        /**
         * Setting message.
         *
         * @param message
         */
        setMessagesData: function (message) {
            var currentMessages = this.getMessageData();
            if (currentMessages.indexOf(message) < 0) {
                this.messagesData.push(message);
            }
        },

        /**
         * Return messages array.
         *
         * @returns {Array}
         */
        getMessageData: function () {
            return this.messagesData;
        },
    });
});
