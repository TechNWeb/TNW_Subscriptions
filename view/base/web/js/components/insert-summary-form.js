/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'TNW_Subscriptions/js/components/payment-form',
        'uiRegistry',
        'underscore'
    ],
    function ($, Component, registry, _) {
        'use strict';

        return Component.extend({
            defaults: {
                ajaxSave: true,
                listens: {
                    responseStatus: 'processResponseStatus'
                },
                insertFormName: null
            },

            /**
             * Process response status.
             */
            processResponseStatus: function () {
                var insertFrom;
                if (this.responseStatus()) {
                    insertFrom = registry.get('index=' + this.insertFormName);
                    insertFrom.destroyInserted();
                    insertFrom.render();
                    this.setMessageFromResponse();
                }
            },

            /**
             * Sets messages from response to components by name
             */
            setMessageFromResponse: function () {
                var result = this.responseData();
                if (result.result) {
                    var messages = result.messages;
                    if (messages) {
                        messages.forEach(function (message) {
                            var tab = registry.get(message.index);
                            tab.setMessagesData(message.message);
                        });
                    }
                }
            },

            /**
             * Render form data.
             */
            renderForm: function (form, params) {
                form.set('visible', true);
                form.destroyInserted();
                form.render(params);
            }
        });
    }
);
