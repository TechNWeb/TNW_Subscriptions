/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/listing'
], function (_, Listing) {
    'use strict';

    return Listing.extend({
        defaults: {
            template: 'TNW_Subscriptions/grid/subscription-order-change-history'
        },

        /**
         * Check is current row have type comment
         *
         * @param row {Object}
         * @returns {number}
         */
        isMessageComment: function (row) {
            return row['is_message_comment'] ? 1 : 0;
        }
    });
});
