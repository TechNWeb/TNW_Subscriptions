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
                render_url: '',
                isLoading: true
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
             * @inheritdoc
             */
            initObservable: function () {
                return this._super()
                    .observe(['isLoading']);
            },

            /**
             * Destroys step data source.
             *
             * @param externalFormName
             */
            resetDataSource: function (externalFormName) {
                var stepDataSource = registry.get(externalFormName);
                if (stepDataSource && stepDataSource.source) {
                    stepDataSource.source.destroy();
                }
            },

            /**
             * Renders current step.
             */
            renderCurrentStep: function () {
                var stepIndex = stepNavigator._getActiveItemIndex();
                var step = stepNavigator.steps()[stepIndex];
                if (step) {
                    var config = registry.get('cart').checkoutConfig;
                    var insertFormContent = registry.get(this.name + '.' + 'insert_form_content');

                    insertFormContent.render_url = config.render_url + '?' + this.getRenderParams(step);
                    insertFormContent.renderSettings.url = insertFormContent.render_url;
                    var externalContentFormName =  step.blockNamespace.content + '.' + step.blockNamespace.content;
                    insertFormContent.externalFormName = externalContentFormName;

                    insertFormContent.ns = step.blockNamespace.content;
                    insertFormContent.params.namespace = step.blockNamespace.content;

                    insertFormContent.cssclass = 'checkout_content_' + step.code;

                    var externalRightFormName = '';
                    if (step.blockNamespace.right) {
                        var insertFormRight = registry.get(this.name + '.' + 'insert_form_right');
                        insertFormRight.render_url = config.render_url + '?' + this.getRenderParams(step);
                        insertFormRight.ns = step.blockNamespace.right;
                        insertFormRight.params.namespace = step.blockNamespace.right;
                        insertFormRight.renderSettings.url = insertFormRight.render_url;
                        externalRightFormName =  step.blockNamespace.right + '.' + step.blockNamespace.right;
                        insertFormRight.externalFormName = externalRightFormName;

                        insertFormRight.cssclass = 'checkout_right_' + step.code;
                    }

                    this.isLoading(true);

                    this.resetDataSource(externalContentFormName);
                    insertFormContent.destroyInserted();
                    insertFormContent.render();

                    if (insertFormRight) {
                        insertFormRight.destroyInserted();
                        insertFormRight.render();
                    }
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

                return $.param(result);
            }
        });
    }
);
