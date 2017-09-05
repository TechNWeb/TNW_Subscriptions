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
                objectId: null,
                objectItemId: null,
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
                    mainForm = registry.get('index = modify_modal_form');
                    if (mainForm) {
                        mainForm.destroyInserted();
                        mainForm.render();
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
                    this.setAdditionalData({
                        objectId: this.objectId,
                        objectItemId: this.objectItemId
                    });
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
