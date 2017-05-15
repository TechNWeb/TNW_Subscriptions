/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/single-checkbox',
    'uiRegistry'
], function (Checkbox, registry) {
    'use strict';

    return Checkbox.extend({
        defaults: {
            clearing: false,
            parentContainer: '',
            parentSelections: '',
            changer: ''
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super().
                observe('elementTmpl');

            return this;
        },

        /**
         * @inheritdoc
         */
        onUpdate: function () {
            if (this.prefer === 'radio' && this.checked() && !this.clearing) {
                this.clearValues();
            }

            this._super();
        },

        /**
         * Clears values in components like this.
         */
        clearValues: function () {
            var records = registry.filter(this.retrieveSearchCriteria(this.parentSelections , this.index)),
                uid = this.uid;

            records.filter(function (comp) {
                return comp.uid !== uid;
            }).each(function (comp) {
                comp.clearing = true;
                comp.clear();
                comp.clearing = false;
            });
        },

        retrieveSearchCriteria: function (parentSelection, index) {
            return 'parentSelections = ' + parentSelection + ' , index = ' + index;
        }
    });
});
