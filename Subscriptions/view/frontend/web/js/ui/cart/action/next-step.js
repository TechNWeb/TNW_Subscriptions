/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'TNW_Subscriptions/js/ui/model/step-navigator',
    'jquery',
    'uiRegistry'
], function (Abstract, stepNavigator, $, registry) {
    'use strict';

    return Abstract.extend({
        defaults: {
            buttonTitle: $.mage.__('Next step >')
        },

        /**
         * Next step button click.
         */
        onNextStepClick: function () {
            var isLoggedIn = registry.get('cart').checkoutConfig.isCustomerLoggedIn;

            var activeCode = stepNavigator.getActiveItemCode();
            if (activeCode === 'products' && isLoggedIn) {
                stepNavigator.navigateTo('shipping');
            } else {
                var currentStep = stepNavigator.getCurrentStep();
                if (currentStep.stepActions) {
                    currentStep.stepActions.forEach(function (element) {
                        var component = registry.async('index = ' + element.targetName),
                            params = [];
                        if (component) {
                            params.unshift(element.actionName);
                            component.apply(component, params);
                        }
                    });
                } else {
                    stepNavigator.navigateNext();
                }
            }

            this.hideButtonIfNeed();
        },

        /**
         * @inheritDoc
         */
        initialize: function () {
            this._super();
            this.hideButtonIfNeed();

            return this;
        },

        /**
         * Hide button next step if need.
         */
        hideButtonIfNeed: function () {
            if (stepNavigator.getActiveItemCode() === 'registration') {
                this.hide();
                $('.tnw-subscriptions-cart-bottom-action').hide();
            }
        }
    });
});
