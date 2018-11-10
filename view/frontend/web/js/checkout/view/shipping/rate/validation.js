/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/validation/rules',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/validation/rules/default'
], function (Component, rateValidationRules, validationRulesDefault) {
    'use strict';

    rateValidationRules.registerRules(validationRulesDefault);

    return Component;
});
