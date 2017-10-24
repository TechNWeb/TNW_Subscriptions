/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
        'jquery',
        'Magento_Ui/js/form/form',
        'TNW_Subscriptions/js/ui/model/step-navigator',
        'uiRegistry',
        'Magento_Ui/js/model/messageList'
    ], function ($, Component, stepNavigator, registry, globalMessageList) {
        'use strict';

        return Component.extend({
            defaults: {
                handle: '',
                render_url: '',
                isLoading: true,
                childResponseData: null,
                listens: {
                    childResponseData: 'processAfterSave'
                },
                currentStepCode: null
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
                    .observe(['isLoading', 'childResponseData']);
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
                    this.currentStepCode = step.code;

                    var config = registry.get('cart').checkoutConfig;
                    var insertFormContent = registry.get(this.name + '.' + 'insert_form_content');

                    insertFormContent.render_url = config.render_url + '?' + this.getRenderParams(step);
                    insertFormContent.renderSettings.url = insertFormContent.render_url;
                    var externalContentFormName =  step.blockNamespace.content + '.' + step.blockNamespace.content;
                    insertFormContent.externalFormName = externalContentFormName;

                    insertFormContent.ns = step.blockNamespace.content;
                    insertFormContent.params.namespace = step.blockNamespace.content;
                    insertFormContent.params.step = step.code;

                    var linksImports = {childResponseData: 'index = ' + step.blockNamespace.content + ':responseData'};
                    this.setLinks(linksImports, 'imports');

                    var externalRightFormName = '';
                    if (step.blockNamespace.right) {
                        var insertFormRight = registry.get(this.name + '.' + 'insert_form_right');
                        insertFormRight.render_url = config.render_url + '?' + this.getRenderParams(step);
                        insertFormRight.ns = step.blockNamespace.right;
                        insertFormRight.params.namespace = step.blockNamespace.right;
                        insertFormRight.renderSettings.url = insertFormRight.render_url;
                        externalRightFormName = step.blockNamespace.right + '.' + step.blockNamespace.right;
                        insertFormRight.externalFormName = externalRightFormName;
                        insertFormRight.params.step = step.code;

                        var linksExport = {
                            currentStepCode: 'index = ' + step.blockNamespace.right + '_data_source:params.step'
                        };
                        this.setLinks(linksExport, 'exports');
                    }

                    this.isLoading(true);

                    this.resetDataSource(externalContentFormName);
                    insertFormContent.destroyInserted();
                    insertFormContent.render();
                    insertFormContent.set('cssclass', 'checkout_content_' + step.code);

                    if (insertFormRight) {
                        insertFormRight.destroyInserted();
                        insertFormRight.render();
                        insertFormRight.set('cssclass', 'checkout_right_' + step.code);
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
            },

            /**
             * Process after form save.
             *
             * @param {Object} data
             */
            processAfterSave: function (data) {
                this.isLoading(false);

                if (!data.error) {
                    stepNavigator.navigateNext();
                } else {
                    this.showError(data.error_messages);
                }
            },

            /**
             * Show error message.
             *
             * @param {String} errorMessage
             */
            showError: function (errorMessage) {
                globalMessageList.addErrorMessage({
                    message: errorMessage
                });
            }
        });
    }
);
