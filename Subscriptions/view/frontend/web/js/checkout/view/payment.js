/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'uiComponent',
    'ko',
    'mage/translate'
], function (
    $,
    _,
    Component,
    ko,
    $t
) {
    'use strict';

    return Component.extend({
        defaults: {
            activeMethod: ''
        },
        isVisible: ko.observable(true),
        quoteIsVirtual: false,
        isPaymentMethodsAvailable: ko.computed(function () {
            return false;
        }),

        /** @inheritdoc */
        initialize: function () {
            this._super();

            return this;
        },

        /**
         * Navigate method.
         */
        navigate: function () {
            var self = this;
        },

        /**
         * @return {*}
         */
        getFormKey: function () {
            return window.checkoutConfig.formKey;
        }
    });
});
