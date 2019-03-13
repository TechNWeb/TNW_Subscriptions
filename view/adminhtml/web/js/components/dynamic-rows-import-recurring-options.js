/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
    'underscore',
    'uiRegistry',
    'jquery',
    'Magento_Ui/js/lib/validation/validator',
    'mage/translate'
], function (DynamicRows, _, registry, $, validator) {
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
            },
            modules: {
                buttonAdd: '${ $.parentName }.container_header.button_add'
            },
            billingFrequenciesCount: ''
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            initMaxId(this.recordData());

            var current = this;
            validator.addRule(
                'validate-billing-frequency-selected-option',
                function (currentValue, rule, params) {
                    var flag = true;
                    _.each(params.allElements, function (element, index) {
                        if (element.dataScope !== params.currentElement.dataScope &&
                            element.value() === currentValue
                        ) {
                            flag = false;
                            return false;
                        }
                    });

                    return flag;
                },
                $.mage.__('The same billing frequency is selected.')
            );

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
            this.checkAddingBillingFrequency(index);

            if (ctx && !_.isNumber(ctx['id'])) {
                ctx['id'] = ++maxId;
            } else if (!ctx) {
                this.showSpinner(true);
                this.addChild(ctx, index, prop);

                // scroll to the added row
                var rows = $$('.tnw-subscriptions-tab .admin__dynamic-rows tr.data-row');
                if (rows.length > 0) {
                    var lastElement = rows[rows.length - 1];

                    $('html,body').animate({scrollTop: $(lastElement).offset().top}, 300);
                }
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
        },

        /**
         * Hide pager if there are no items in grid.
         */
        hidePager: function() {
            var childs = this.getChildItems(),
                grid,
                pager;

            if (childs.length == 0) {
                grid = $('.recurring-options');

                if (grid.length > 0) {
                    pager = grid.find('.admin__control-table-pagination');
                    if (pager.length > 0) {
                        pager[0].hide();
                    }
                }
            }
        },

        /**
         * Disable/enable button.
         * If grid elements count more than max.
         *
         * @param {String} rowIndex
         * @return {void}
         */
        checkAddingBillingFrequency: function (rowIndex) {
            var button = this.buttonAdd();
            button.set('disabled', false);
            if (rowIndex === undefined && this._elems.length == this.billingFrequenciesCount) {
                 button.set('disabled', true);
            }
        },

        /**
         * Rewrite whole method, because in magento version (EE 2.2.0) method is updated
         * and not suitable for our component
         *
         * {inheritdoc}
         */
        deleteRecord: function (index, recordId) {
            var recordInstance,
                lastRecord,
                recordsData,
                childs;

            if (this.deleteProperty) {
                recordInstance = _.find(this.elems(), function (elem) {
                    return elem.index === index;
                });
                recordInstance.destroy();
                this.elems([]);
                this._updateCollection();
                this.removeMaxPosition();
                this.recordData()[recordInstance.index][this.deleteProperty] = this.deleteValue;
                this.recordData.valueHasMutated();
                childs = this.getChildItems();

                if (childs.length > this.elems().length) {
                    this.addChild(false, childs[childs.length - 1][this.identificationProperty], false);
                }
            } else {
                this.update = true;

                if (~~this.currentPage() === this.pages()) {
                    lastRecord =
                        _.findWhere(this.elems(), {
                            index: this.startIndex + this.getChildItems().length - 1
                        }) ||
                        _.findWhere(this.elems(), {
                            index: (this.startIndex + this.getChildItems().length - 1).toString()
                        });

                    lastRecord.destroy();
                }

                this.removeMaxPosition();
                recordsData = this._getDataByProp(recordId);
                this._updateData(recordsData);
                this.update = false;
            }

            if (this.pages() < ~~this.currentPage()) {
                this.currentPage(this.pages());
            }

            this._sort();
            this.hidePager();
            this.checkAddingBillingFrequency(index);
        }
    });
});
