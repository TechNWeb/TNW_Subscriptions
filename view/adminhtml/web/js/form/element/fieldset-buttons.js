/**
 * Copyright © 2019 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'mageUtils',
    'uiRegistry',
    'underscore'
], function (Fieldset, utils, registry, _) {
    'use strict';

    return Fieldset.extend({
        defaults: {
            buttons: []
        },

        buttonAction: function (actions) {
            actions.forEach(this.applyAction, this);
        },

        /**
         * Apply action on target component,
         * but previously create this component from template if it is not existed
         *
         * @param {Object} action - action configuration
         */
        applyAction: function (action) {
            var targetName = action.targetName,
                params = utils.copy(action.params) || [],
                actionName = action.actionName,
                target;

            target = registry.async(targetName);

            if (target && typeof target === 'function' && actionName) {
                params.unshift(actionName);
                target.apply(target, params);
            }
        }
    });
});
