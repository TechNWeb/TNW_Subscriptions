/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define(
    [
        'jquery',
        'mage/storage',
        'Magento_Ui/js/model/messageList'
    ],
    function($, storage, globalMessageList) {
        'use strict';
        var callbacks = [],
            action = function(loginData, redirectUrl, isGlobal, messageContainer) {
                messageContainer = messageContainer || globalMessageList;
                return storage.post(
                    'customer/ajax/login',
                    JSON.stringify(loginData),
                    isGlobal
                ).always(function (response) {
                    callbacks.forEach(function(callback) {
                        callback(loginData);
                    });
                }).done(function (response) {
                    if (response.errors) {
                        messageContainer.addErrorMessage(response);
                    } else {
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                        } else if (response.redirectUrl) {
                            window.location.href = response.redirectUrl;
                        } else {
                            location.reload();
                        }
                    }
                }).fail(function () {
                    messageContainer.addErrorMessage({'message': 'Could not authenticate. Please try again later'});
                });
            };

        action.registerLoginCallback = function(callback) {
            callbacks.push(callback);
        };

        return action;
    }
);
