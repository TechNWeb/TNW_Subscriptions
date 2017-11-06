/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'TNW_Subscriptions/js/components/insert-form',
    'mageUtils',
    'jquery',
    'uiRegistry'
], function (Insert, utils, $) {
    'use strict';

    return Insert.extend({
        /** @inheritdoc */
        render: function (params) {
            $('body').trigger('processStart');
            this._super();
            return this;
        }
    });
});
