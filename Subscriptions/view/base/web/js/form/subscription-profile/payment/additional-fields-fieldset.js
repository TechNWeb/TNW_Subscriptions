/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'underscore'
], function ($, Collapsible, registry, _) {
    'use strict';

    return Collapsible.extend({
        defaults: {
            complexComponents: [
                'fieldset',
                'container'
            ]
        },

        /**
         * Change fieldset visibility and clear child elems values if fieldset was hidden
         *
         * @param {boolean} checkBoxChecked
         * @returns {void}
         */
        changeVisibility: function (checkBoxChecked) {
            var form;
            this.visible(checkBoxChecked);
            if (!checkBoxChecked) {
                this.clearElemsData(this.elems());
            }
        },

        /**
         * Clear elements values
         *
         * @param {mixed} elems
         * @returns void
         */
        clearElemsData: function(elems) {
            var form = this;
            var rules = {};
            _.each(elems, function (field, code) {
                if ($.inArray(field.componentType, form.complexComponents) != -1) {
                    form.clearElemsData(field.elems());
                } else {
                     field.restoreToDefault();
                    field.error('');
                }
            });
        }
    });
});
