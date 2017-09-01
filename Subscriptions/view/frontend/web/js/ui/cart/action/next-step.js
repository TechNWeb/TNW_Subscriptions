/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'TNW_Subscriptions/js/ui/model/step-navigator'
], function (Abstract, stepNavigator) {
    'use strict';

    return Abstract.extend({
        defaults: {
            buttonTitle: 'Next step >'
        },

        onNextStepClick: function () {
            stepNavigator.navigateNext();
        }
    });
});
