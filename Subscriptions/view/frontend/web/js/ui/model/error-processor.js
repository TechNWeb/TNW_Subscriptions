/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'mage/url',
        'Magento_Ui/js/model/messageList'
    ],
    function (url, globalMessageList) {
        'use strict';

        return {

            /**
             * Process error
             *
             * @param {Object} response
             * @param {Object} messageContainer
             */
            process: function (response, messageContainer) {
                messageContainer = messageContainer || globalMessageList;
                if (response.status == 401) {
                    window.location.replace(url.build('customer/account/login/'));
                } else {
                    messageContainer.addErrorMessage(JSON.parse(response.responseText));
                }
            }
        };
    }
);
