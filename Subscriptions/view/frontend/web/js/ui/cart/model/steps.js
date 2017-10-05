/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
        'jquery',
        'Magento_Ui/js/form/form',
        'TNW_Subscriptions/js/ui/model/step-navigator',
        'uiRegistry'
    ], function ($, Component, stepNavigator, registry) {
        'use strict';

        return Component.extend({
            defaults: {
                handle: '',
                render_url: ''
            },

            /**
             * @inheritDoc
             */
            initialize: function () {
                this._super();
                var self = this;
                $.each(stepNavigator.steps(), function (key, value) {
                    value.isActive.subscribe(function (changes) {
                        if (changes) {
                            self.renderCurrentStep();
                        }
                    });
                });
                this.renderCurrentStep();

                return this;
            },

            /**
             * Renders current step.
             */
            renderCurrentStep: function () {
                var stepIndex = stepNavigator._getActiveItemIndex();
                var step = stepNavigator.steps()[stepIndex];
                if (step) {
                    var provider = registry.get('subscriptionsProvider');
                    var insertForm = registry.get(this.name + '.' + 'insert_form');
                    insertForm.render_url = provider.render_url + '?' + this.getRenderParams(step);
                    insertForm.ns = step.blockNamespace;
                    insertForm.params.namespace = step.blockNamespace;
                    insertForm.renderSettings.url = insertForm.render_url;
                    insertForm.destroyInserted();
                    insertForm.render();
                }
            },

            /**
             * Returns url params.
             *
             * @param step
             * @returns {*}
             */
            getRenderParams: function (step) {
                var result = {};
                if (step.requestFieldName && step.requestFieldValue){
                    result[step.requestFieldName] = step.requestFieldValue;
                }
                result['handle'] = this.handle + '_' + step.code;
                result['form_key'] = window.FORM_KEY;

                return $.param(result);
            }
        });
    }
);
