/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
        'uiComponent',
        'TNW_Subscriptions/js/ui/model/step-navigator'
    ], function (Component, stepNavigator) {
        'use strict';

        return Component.extend({
            productsStepIndex: 0,
            registrationStepIndex: 1,
            addressStepIndex: 2,
            paymentStepIndex: 3,
            thankYouStepIndex: 4,

            getActiveIndex: function () {
                return stepNavigator._getActiveItemIndex();
            }
        });
    }
);
