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
            getActiveIndex: function () {
                return stepNavigator._getActiveItemIndex();
            }
        });
    }
);
