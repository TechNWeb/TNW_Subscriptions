/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/form/components/group'
], function (_, Collection) {
    'use strict';

    return Collection.extend({
        defaults: {
            groupError: ''
        },
        /**
         * Calls initObservable of parent class.
         * Defines observable properties of instance.
         *
         * @return {Object} - reference to instance
         */
        initObservable: function () {
            this._super()
                .observe(['groupError', 'error']);

            return this;
        },

        /**
         * Extends 'additionalClasses' object.
         *
         * @returns {Group} Chainable.
         */
        _setClasses: function () {
            var addtional = this.additionalClasses,
                classes;

            if (_.isString(addtional)) {
                addtional = this.additionalClasses.split(' ');
                classes = this.additionalClasses = {};

                addtional.forEach(function (name) {
                    classes[name] = true;
                }, this);
            }

            _.extend(this.additionalClasses, {
                'admin__control-grouped': !this.breakLine,
                'admin__control-fields': this.breakLine,
                required:   this.required,
                _error:     this.error,
                _disabled:  this.disabled
            });

            return this;
        }
    });
});
