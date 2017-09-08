/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'TNW_Subscriptions/js/ui/model/step-navigator',
    'jquery'
], function (Abstract, stepNavigator, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            buttonTitle: 'Next step >'
        },

        /**
         * Next step button click.
         */
        onNextStepClick: function () {
            stepNavigator.navigateNext();

            this.hideButtonIfNeed();

            this.registrationStep();
        },

        /**
         * @inheritDoc
         */
        initialize: function () {
            this._super();
            this.hideButtonIfNeed();

            this.registrationStep();
            return this;
        },

        /**
         * Actions for registration step.
         */
        registrationStep: function () {
            if (stepNavigator._getActiveItemIndex() == 1) {
                this.missRegistrationIfLogin();
                if (stepNavigator._getActiveItemIndex() == 1) {
                    this.sendAjaxToChangeUrl('tnw_subscriptions/cart/index/', '#registration');
                }
            }
        },

        /**
         * Not need registration if customer is log in.
         */
        missRegistrationIfLogin: function () {
            if (window.tnwSubscriptionsCheckoutConfig.isCustomerLoggedIn) {
                stepNavigator.navigateNext();
            }
        },

        /**
         * Hide button next step if need.
         */
        hideButtonIfNeed: function () {
            if (stepNavigator._getActiveItemIndex() == 4 ||
                stepNavigator._getActiveItemIndex() == 1
            ) {
                this.hide();
            }
        },

        /**
         * Send ajax to change redirect url after login.
         */
        sendAjaxToChangeUrl: function (routePath, hash) {
            $.ajax({
                showLoader: true,
                url: window.tnwSubscriptionsCheckoutConfig.changeAfterLoginUrl,
                data: {
                    form_key: window.FORM_KEY,
                    'route_path': routePath,
                    'hash': hash
                },
                type: "POST",
                dataType: 'json'
            })
        }
    });
});
