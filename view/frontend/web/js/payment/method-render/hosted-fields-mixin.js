/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([], function () {
    return function (HostedFields) {
        return HostedFields.extend({
           isSubscriptionModuleEnabled() {
               return window.checkoutConfig.isSubscriptionEnabled;
           }
        })
    }
})
