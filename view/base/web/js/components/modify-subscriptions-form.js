define(
    [
        'jquery',
        'TNW_Subscriptions/js/components/subscriptions-form',
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
                requestFields: {},
                additionalData: {},
                productsFormName: 'modify_modal_form',
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
                        if ($('.account.tnw_subscriptions-subscription-edit .block-next-payment .cost').length) {
                            window.location.reload();
                        } else {
                            mainForm.destroyInserted();
                            mainForm.render();
                        }
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
             * Hide and show edit and remove buttons on products form.
             *
             * @param {Object} current
             * @param {boolean} previewMode
             */
            hideEditButtons: function (current, previewMode) {
                _.each(registry.filter('index = form'), function (form) {
                    if (form !== current) {
                        _.each(form.editButtons, function (item) {
                                var editButton = registry.get(item);
                                editButton.visible(editButton.buttonVisibility && !previewMode);
                            }
                        );
                        _.each(registry.filter('index = remove_button'), function (removeButton) {
                            removeButton.visible(!previewMode)
                        });
                    }
                });
            },

            /**
             * Toggles "previewMode" property.
             */
            togglePreviewMode: function () {
                var current = this;
                var previewMode = this.previewMode();
                _.each(this.editButtons, function (item) {
                    if (!previewMode) {
                        current.hideEditButtons(current, previewMode);
                        current.buttonPreviewMode(false);
                        registry.get(item).deactivate();
                    } else {
                        current.hideEditButtons(current, previewMode);
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
                this.hideEditButtons(this, !this.buttonPreviewMode());
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
                    this.filterRequestFields();
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
            },

            /**
             * Remove excluded fields from saving data.
             */
            filterRequestFields: function () {
                var itemId = this.additionalData.objectItemId,
                    requestFields = this.requestFields;
                if (itemId){
                    var item = this.source.get('data.item_' + itemId);
                    if (item){
                        _.each(Object.keys(item), function (name) {
                            if (_.indexOf(requestFields, name) === -1){
                                delete item[name];
                            }
                        }, this);
                        this.source.set('data.item_' + itemId, item);
                    }
                }

                return this;
            },

            /**
             * update product options on current form and save form.
             */
            updateProductOptions: function() {
                var data,
                    itemId,
                    productOptionsData = registry.get('index=' + this.source.editOptionsForm).source.data,
                    currentItemData;

                if (typeof productOptionsData != 'undefined'
                    && typeof productOptionsData.super_attribute != 'undefined'
                    && typeof productOptionsData.item_data != 'undefined'
                ) {
                    itemId = productOptionsData.item_data.item_id;

                    if (itemId && typeof this.source.data['item_' + itemId] != 'undefined') {
                        currentItemData = this.source.data['item_' + itemId];
                        currentItemData['super_attribute'] = productOptionsData.super_attribute;

                        data = {
                            'objectId': productOptionsData.item_data.quote_id,
                            'objectItemId': itemId
                        };
                    }
                    this.save('', data);
                }
            }
        });
    }
);
