/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
    'underscore',
    'uiRegistry',
    'jquery'
], function (DynamicRows, _, registry, $) {
    'use strict';

    var maxId = 0,

    /**
     * Stores max id value of the options from recordData once on initialization
     * @param {Array} data - array with records data
     */
    initMaxId = function (data) {
        if (data && data.length) {
            maxId = _.max(data, function (record) {
                return parseInt(record['id'], 10) || 0;
            })['id'];
            maxId = parseInt(maxId, 10) || 0;
        }
    };

    return DynamicRows.extend({
        defaults: {
            mappingSettings: {
                enabled: false,
                distinct: false
            },
            update: true,
            map: {
                'id': 'id'
            },
            identificationProperty: 'id',
            identificationDRProperty: 'id',
            defaultRowIndexProperty: 'default_billing_frequency',
            //TODO: don't know how to clear values for "Default" field on other pages. They are not present in registry.
            pageSize: 9999,
            dndConfig: {
                component: 'TNW_Subscriptions/js/components/dynamic-rows/dnd'
            }
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            initMaxId(this.recordData());

            return this;
        },

        /** @inheritdoc */
        initDnd: function () {
            this._super();
            if (this.dndConfig.enabled) {
                $(document).on(this.dndConfig.name + ':afterSetPosition', this.refreshDefaultRecord.bind(this));
            }

            return this;
        },

        /**
         * Set empty array to dataProvider
         */
        clearDataProvider: function () {
            this.source.set(this.dataProvider, []);
        },

        /** @inheritdoc */
        processingAddChild: function (ctx, index, prop) {
            if (ctx && !_.isNumber(ctx['id'])) {
                ctx['id'] = ++maxId;
            } else if (!ctx) {
                this.showSpinner(true);
                this.addChild(ctx, index, prop);

                return;
            }

            this._super(ctx, index, prop);
        },

        /**
         * Mutes parent method
         */
        updateInsertData: function () {
            return false;
        },

        /**
         * Refresh "default" record on reorder.
         *
         * @param {Event} event
         * @param {Object} elem
         * @return {void}
         */
        refreshDefaultRecord: function (event, elem) {
            var records = this.retrieveElements(this.defaultRowIndexProperty);
            _.each(records, function (record) {
                if (record.value()) {
                    record.checked.valueHasMutated();
                }
            });
        },

        /**
         * Retrieve elements by index.
         *
         * @param {String} index
         * @return {Array}
         */
        retrieveElements: function(index) {
            return registry.filter('parentSelections = ' + this.index + ', index = ' + index + '');
        }
    });
});
