/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'TNW_Subscriptions/js/components/payment-form',
        'uiRegistry',
        'underscore',
        'mage/translate'
    ],
    function ($, Component, registry, _) {
        'use strict';

        return Component.extend({
            defaults: {
                detailsView: '#payment-details-view',
                methodContent: '.inner-block.tnw_subscriptions',
                viewSelectorBlock: '#payment-details-view',
                editSelectorBlock: '#payment-details-edit',
                listens: {
                    responseData: 'processResponseData'
                },
                complexComponents: [
                    'fieldset',
                    'container'
                ]
            },

            /**
             * Run before form submit
             */
            beforeSubmit: function() {
                $('body').trigger('processStart');
                var parentResult = this._super();

                if (!parentResult) {
                    $('body').trigger('processStop');
                }

            },

            /**
             * Process response data.
             *
             * @param {Object} data
             */
            processResponseData: function (data) {
                var viewElem = $(this.detailsView),
                    methodContentBlock = $(viewElem.find(this.methodContent)),
                    additionalFieldsets;

                if (!data.error) {
                    methodContentBlock.html(data.data.payment);

                    additionalFieldsets = registry.filter('index = additional_fields');

                    this.clearElemsData(additionalFieldsets);
                    this.cancelEdit();
                }
                $('body').trigger('processStop');
            },

            /**
             * Hide edit block and show view block.
             */
            cancelEdit: function() {
                $(this.viewSelectorBlock).show();
                $(this.editSelectorBlock).hide();
            },

            /**
             * Trigger save
             *
             * @param {Object} errors
             */
            triggerSave: function(errors) {
                this._super();
                $('body').trigger('processStop');
            },

            /**
             * Clear elements values
             *
             * @param {mixed} elems
             */
            clearElemsData: function(elems) {
                var form = this;
                _.each(elems, function (field, code) {
                    if ($.inArray(field.componentType, form.complexComponents) != -1) {
                        form.clearElemsData(field.elems());
                    } else {
                        field.value('');
                    }

                });
            }
        });
    }
);
