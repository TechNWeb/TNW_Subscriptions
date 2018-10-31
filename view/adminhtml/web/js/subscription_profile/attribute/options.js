/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    "jquery",
    "Magento_Catalog/js/options"
], function($) {
    'use strict';

    return {
        "TNW_Subscriptions/js/subscription_profile/attribute/options": function () {
            // Need to manually render options form
            // because in product attribute form this event called by swatches module.
            $('#manage-options-panel').trigger('render');
        }
    }
});
