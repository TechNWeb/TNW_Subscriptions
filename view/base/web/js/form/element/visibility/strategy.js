/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'uiRegistry'
], function (_, registry) {
    'use strict';

    /**
     * Returns dependency elements indexes.
     *
     * @param {Array} visibilityDependencies
     * @return {Array}
     */
    var resolveDependencies = function(visibilityDependencies) {
        var dependencies = [];
        _.each(visibilityDependencies, function (dependency) {
            var propertyData = dependency.split(':'),
                index = propertyData[0];
            if (index[0] == '!') {
                index = index.substring(1);
            }
            dependencies.push(index);
        });

        return dependencies;
    };

    return {
        defaults: {
            visibilityDependencies: [],
            visibilityLogic: 'AND'
        },

        /** @inheritdoc */
        initialize: function () {
            var dependencies;
            this._super();

            if (!_.isEmpty(this.visibilityDependencies)) {
                dependencies = resolveDependencies(this.visibilityDependencies);
                // Set the initial visibility of our fields after all dependency components are loaded.
                registry.promise(dependencies).done(_.bind(function () {
                    this.refreshVisibility();
                }, this));
            }
        },

        /** @inheritdoc */
        initLinks: function () {
            if (!_.isEmpty(this.visibilityDependencies)) {
                _.each(this.visibilityDependencies, function (dependency) {
                    if (dependency[0] == '!') {
                        dependency = dependency.substring(1);
                    }
                    this.listens[dependency] = 'refreshVisibility';
                }.bind(this));
            }

            return this._super();
        },

        /**
         * Refresh visibility state.
         *
         * @return void
         */
        refreshVisibility: function () {
            var visibility = true;

            _.each(this.visibilityDependencies, function (dependency) {
                var index,
                    propertyName,
                    dependencyValue,
                    propertyData = dependency.split(':');

                index = propertyData[0];
                if (index[0] == '!') {
                    index = index.substring(1);
                }
                propertyName = propertyData[1];
                dependencyValue = registry.get(index)[propertyName]();
                if (dependency[0] == '!') {
                    dependencyValue = !dependencyValue;
                }

                if (this.visibilityLogic === 'AND') {
                    visibility = visibility && dependencyValue;
                } else {
                    visibility = visibility || dependencyValue;
                }
            }.bind(this));

            this.visible(visibility);
        }
    };
});
