/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

/* eslint-disable no-undef */
// jscs:disable jsDoc

define([
    'jquery',
    'Magento_Catalog/js/options'
], function (jQuery) {
    'use strict';

    return {
        'TNW_Subscriptions/js/profile/product/attribute/options' : function () {
            jQuery('#manage-options-panel').trigger('render');
        }
    }

});
