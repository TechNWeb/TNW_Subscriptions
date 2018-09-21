/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/single-checkbox',
    'uiRegistry',
    'underscore'
], function (Checkbox, registry, _) {
    'use strict';

    return Checkbox.extend({
        defaults: {
            template: 'ui/form/element/checkbox-set',
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

            records = records.filter(function (comp) {
                return comp.uid !== uid;
            });

            _.each(records, function (comp) {
                comp.clearing = true;
                comp.clear();
                comp.clearing = false;
            });

        },

        retrieveSearchCriteria: function (parentSelection, index) {
            return 'parentSelections = ' + parentSelection + ' , index = ' + index;
        },

        /**
         * @inheritdoc
         */
        setInitialValue: function () {
            var existIsDefault = false;
            var records = registry.filter(this.retrieveSearchCriteria(this.parentSelections , this.index)),
                uid = this.uid;

            records = records.filter(function (comp) {
                return comp.uid !== uid;
            });

            _.each(records, function (comp) {
                if (comp.value() == 1) {
                    return existIsDefault = true;
                }
            });

            if (existIsDefault) {
                this.value(0);
            }

            this._super();

            return this;
        },
    });
});
