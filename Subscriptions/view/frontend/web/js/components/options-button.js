/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/edit-button'
], function (Button) {
    'use strict';

    return Button.extend({
        defaults: {
            elementTmpl: 'TNW_Subscriptions/form/element/edit-button',
            configureUrl: ''
        },

        /**
         * 'Edit options' button click action.
         *
         * @return void
         */
        editOptions: function () {
            window.location = this.configureUrl;
        }
    });
});
