/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
        'jquery',
        'Magento_Ui/js/form/form',
        'TNW_Subscriptions/js/ui/model/step-navigator',
        'uiRegistry',
        'Magento_Ui/js/model/messageList',
        'underscore'
    ], function ($, Component, stepNavigator, registry, globalMessageList, _) {
        'use strict';

        return Component.extend({
            defaults: {
                handle: '',
                render_url: '',
                isLoading: true,
                loadingQueue: [],
                childResponseData: null,
                listens: {
                    childResponseData: 'processAfterSave',
                    loadingQueue: 'checkLoadingQueue'
                },
                currentStepCode: null,
                modules: {
                    nextStep: 'index = next_step',
                    bottomNextStep: 'index = bottom_next_step',
                    cart: 'cart'
                }
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

                return this;
            },

            /**
             * Renders current step after self rendering.
             */
            onElementRender: function () {
                this.renderCurrentStep();
            },

            /**
             * @inheritdoc
             */
            initObservable: function () {
                return this._super()
                    .observe(['isLoading', 'childResponseData', 'loadingQueue']);
            },

            /**
             * Destroys step data source.
             *
             * @param externalFormName
             */
            resetDataSource: function (externalFormName) {
                var stepDataSource = registry.get(externalFormName + '_data_source');
                if (stepDataSource) {
                    stepDataSource.destroy();
                }
            },

            /**
             * Renders current step.
             */
            renderCurrentStep: function () {
                var stepIndex = stepNavigator._getActiveItemIndex();
                var step = stepNavigator.steps()[stepIndex];
                if (step) {
                    var current = this;
                    _.each(current.elems(), function (item) {
                        item.destroyInserted();
                    });
                    _.each(step.blocks, function (item) {
                        current.addToLoadingQueue(item.handle);
                    });
                    this.currentStepCode = step.code;
                    var config = this.cart().checkoutConfig;
                    _.each(step.blocks, function (item, index) {
                        var form = registry.get(current.name + '.' + 'insert_form_' + index);
                        form.render_url = config.render_url + '?' + current.getRenderParams(step);
                        form.renderSettings.url = form.render_url;
                        var externalFormName =  item.handle + '.' + item.handle;
                        form.externalFormName = externalFormName;
                        form.ns = item.handle;
                        form.params.namespace = item.handle;
                        form.params.step = step.code;
                        if (item.type === 'form') {
                            var linksImports = {
                                childResponseData: 'index = ' + item.handle + ':responseData'
                            };
                            current.setLinks(linksImports, 'imports');
                            current.resetDataSource(externalFormName);
                        } else if (item.type === 'listing') {
                            current.resetDataSource(externalFormName);
                            var linksExport = {
                                currentStepCode: 'index = ' + item.handle + '_data_source:params.step'
                            };
                            current.setLinks(linksExport, 'exports');
                        }
                        current.renderBlock(form, step);
                    });
                }
                this.modifyNextStepButton(step);
            },

            /**
             * Modifies next step button according to step.
             *
             * @param {Object} step
             */
            modifyNextStepButton: function (step) {
                this.nextStep().hideButtonIfNeed();
                if (step.nextButtonTitle){
                    this.nextStep().buttonTitle(step.nextButtonTitle);
                    this.bottomNextStep().buttonTitle(step.nextButtonTitle)
                }
            },

            /**
             * Render form block.
             *
             * @param {Object} form
             * @param {Object} step
             * @returns {boolean}
             */
            renderBlock: function (form, step) {
                if (!form && !stepCode) {
                    return false;
                }
                form.destroyInserted();
                form.set('cssclass', form.cssPrefix + '_' +  step.code);
                form.contentSelector = form.cssPrefix + '_' +  step.code;
                form.render();
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
            },

            /**
             * Add element to loading queue.
             *
             * @param {String} initiator
             */
            addToLoadingQueue: function (initiator) {
                if (_.indexOf(this.loadingQueue(), initiator) === -1) {
                    this.loadingQueue.push(initiator);
                }
            },

            /**
             * Remove current element from loading queue.
             *
             * @param {String} initiator
             */
            removeFromLoadingQueue: function (initiator) {
                var index = _.indexOf(this.loadingQueue(), initiator);
                if (index !== -1) {
                    this.loadingQueue.splice(index, 1);
                }
            },

            /**
             * Check show/hide spinner.
             *
             * @param {Array} loadingQueue
             */
            checkLoadingQueue: function (loadingQueue) {
                if (loadingQueue.length) {
                    this.isLoading(true);
                } else {
                    this.isLoading(false);
                }
            },

            /**
             * Redirect to catalog.
             *
             * @return void
             */
            redirectToCatalog: function () {
                var base_url = this.cart().checkoutConfig.base_url;
                if (base_url) {
                    window.location.href = base_url;
                }
            }
        });
    }
);
