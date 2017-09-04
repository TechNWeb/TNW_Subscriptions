/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/button',
    'TNW_Subscriptions/js/ui/model/step-navigator'
], function (Button, stepNavigator) {
    'use strict';

    return Button.extend({
        defaults: {
            buttonTitle: 'Next step >'
        },

        onNextStepClick: function () {
            stepNavigator.navigateNext();

            if (stepNavigator._getActiveItemIndex() == 4) {
                this.destroy();
            }
        }
    });
});
