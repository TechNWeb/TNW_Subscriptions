define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        'uiRegistry',
        'underscore'
    ],
    function ($, Component, registry, _) {
        'use strict';

        return Component.extend({
            defaults: {
                ajaxSave: true,
                previewMode: true,
                buttonPreviewMode: true,
                editButtons: {},
                additionalData: {},
                productsFormName: null,
                listens: {
                    responseStatus: 'processResponseStatus'
                }
            },

            /** @inheritdoc */
            initObservable: function () {
                return this._super()
                    .observe(['previewMode', 'buttonPreviewMode']);
            },

            /**
             * Process response status.
             */
            processResponseStatus: function () {
                var mainForm,
                    modal;

                if (this.responseStatus()) {
                    modal = registry.get('index = modifyModal');
                    if (modal) {
                        modal.wasModified(true);
                        if (this.responseData().objects_count !== 'undefined'
                            && this.responseData().objects_count === 0
                        ) {
                            modal.closeModal();
                            return;
                        }
                    }
                    mainForm = registry.get('index = ' + this.productsFormName);
                    if (mainForm) {
                        mainForm.destroyInserted();
                        mainForm.render();
                    }
                    this.setMessageFromResponse();
                }
            },

            /**
             * Sets messages from response to components by name
             */
            setMessageFromResponse: function () {
                var result = this.responseData();
                if (result.result) {
                    var messages = result.messages;
                    if (messages) {
                        messages.forEach(function (message) {
                            var tab = registry.get(message.index);
                            tab.setMessagesData(message.message);
                        });
                    }
                }
            },

            /**
             * Toggles "previewMode" property.
             */
            togglePreviewMode: function () {
                var current = this;
                var previewMode = this.previewMode();
                _.each(this.editButtons, function (item) {
                    if (!previewMode) {
                        current.buttonPreviewMode(false);
                        registry.get(item).deactivate();
                    } else {
                        current.buttonPreviewMode(true);
                        registry.get(item).activate();
                    }
                });
                this.previewMode(!previewMode);
            },

            /**
             * Toggles "buttonPreviewMode" property.
             */
            toggleButtonPreviewMode: function () {
                this.buttonPreviewMode(!this.buttonPreviewMode());
            },

            /**
             * Validate and save form.
             *
             * @param {String} redirect
             * @param {Object} data
             */
            save: function (redirect, data) {
                this.validate();

                if (!this.additionalInvalid && !this.source.get('params.invalid')) {
                    this.setAdditionalData(this.additionalData);
                    this.setAdditionalData(data)
                        .submit(redirect);
                }
            },

            /**
             * Removes item. Calls save with remove param.
             */
            removeProduct: function () {
                this.setAdditionalData({
                    remove: true
                });
                this.save();
            }
        });
    }
);
