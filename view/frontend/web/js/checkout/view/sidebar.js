/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'uiComponent',
    'ko',
    'jquery',
    'TNW_Subscriptions/js/checkout/model/sidebar'
], function (Component, ko, $, sidebarModel) {
    'use strict';

    return Component.extend({
        /**
         * @param {HTMLElement} element
         */
        setModalElement: function (element) {
            sidebarModel.setPopup($(element));
        }
    });
});
