/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/ui-select',
    'mage/translate'
], function ($, _, registry, UiSelect) {
    'use strict';

    return UiSelect.extend({
        defaults: {
            previousGroup: null,
            groupsConfig: {},
            valuesMap: {},
            indexesMap: {},
            filterPlaceholder: 'ns = ${ $.ns }, parentScope = ${ $.parentScope }',
            periodLabels: {},
            warningMessage: '',
            validationParams: {
                currentElement: null
            }
        },

        /**
         * Initialize component.
         * @returns {Element}
         */
        initialize: function () {
            return this
                ._super()
                .initMapping()
                .updateComponents(this.initialValue, true)
                .updateHeader(this.initialValue);
        },

        /**
         * Create additional mappings.
         *
         * @returns {Element}
         */
        initMapping: function () {
            _.each(this.groupsConfig, function (groupData, group) {
                _.each(groupData.values, function (value) {
                    this.valuesMap[value] = group;
                }, this);

                _.each(groupData.indexes, function (index) {
                    if (!this.indexesMap[index]) {
                        this.indexesMap[index] = [];
                    }

                    this.indexesMap[index].push(group);
                }, this);
            }, this);

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         *
         * @param {String} currentValue
         * @returns {*}
         */
        onUpdate: function (currentValue) {
            this.validationParams.currentElement = this;
            this.validationParams.allElements = this.retrieveElements(this.index);
            this.validate('validate-billing-frequency-selected-option', currentValue);

            this.updateComponents(currentValue);
            this.updateHeader(currentValue);

            return this._super();
        },

        /**
         * Show, hide or clear components based on the current type value.
         *
         * @param {String} currentValue
         * @param {Boolean} isInitialization
         * @returns {Element}
         */
        updateComponents: function (currentValue, isInitialization) {
            var currentGroup = this.valuesMap[currentValue];

            if (currentGroup !== this.previousGroup) {
                _.each(this.indexesMap, function (groups, index) {
                    var template = this.filterPlaceholder + ', index = ' + index,
                        visible = groups.indexOf(currentGroup) !== -1,
                        component;

                    switch (index) {
                        case 'container_type_static':
                        case 'values':
                            template = 'ns=' + this.ns +
                                ', dataScope=' + this.parentScope +
                                ', index=' + index;
                            break;
                    }

                    /*eslint-disable max-depth */
                    if (isInitialization) {
                        registry.async(template)(
                            function (currentComponent) {
                                currentComponent.visible(visible);
                            }
                        );
                    } else {
                        component = registry.get(template);

                        if (component) {
                            component.visible(visible);

                            /*eslint-disable max-depth */
                            if (_.isFunction(component.clear)) {
                                component.clear();
                            }
                        }
                    }
                }, this);

                this.previousGroup = currentGroup;
            }

            return this;
        },

        /**
         * Updates Grid header depends on the source data.
         *
         * @param currentValue
         */
        updateHeader:function (currentValue) {
            var container = this.getContainer();
            if (container != null) {
                var message = 'Please select billing frequency';
                var currentLabel = '';
                if (typeof this.periodLabels[currentValue] != 'undefined') {
                    message = 'Billed & Shipped every %s';
                    currentLabel = this.periodLabels[currentValue];
                }

                message = $.mage.__(message).replace('%s', currentLabel);
                container.label(message);
            }
        },

        /**
         * Retrieve grid container.
         *
         * @returns {*}
         */
        getContainer: function () {
            var container = null;
            var regExp = /.*?(\.\d+)/ig;
            var containerName = regExp.exec(this.parentName);
            if (containerName != null) {
                containerName = containerName[0];
                container = registry.get(containerName);
            }

            return container;
        },

        /**
         * Retrieve elements by index.
         *
         * @param {String} index
         * @return {Array}
         */
        retrieveElements: function(index) {
            return registry.filter('index = ' + index + '');
        }
    });
});
