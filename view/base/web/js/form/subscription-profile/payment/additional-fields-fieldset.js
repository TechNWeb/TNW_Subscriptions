/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/form/components/fieldset',
    'underscore'
], function ($, Collapsible, _) {
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
         * @return void
         */
        changeVisibility: function(checkBoxChecked) {
            this.visible(checkBoxChecked);
            if (!checkBoxChecked) {
                this.clearElemsData(this.elems());
            }
        },

        /**
         * Clear elements values
         *
         * @param {mixed} elems
         * @return void
         */
        clearElemsData: function(elems) {
            var fieldSet = this,
                rules = {};
            _.each(elems, function (field, code) {
                if ($.inArray(field.componentType, fieldSet.complexComponents) != -1) {
                    fieldSet.clearElemsData(field.elems());
                } else if (field.inputName.match(/publicHash/g) !== null) {
                    return false;
                } else {
                    field.restoreToDefault();
                    field.error('');
                }
            });
        }
    });
});
