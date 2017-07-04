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
                modalForm: null,
                ajaxSave: true,
                configurableModal: null,
                listens: {
                    responseStatus: 'processResponseStatus'
                }
            },

            /**
             * Process response status.
             */
            processResponseStatus: function () {
                var mainModal,
                    grid,
                    subProductListing;

                if (this.responseStatus()) {
                    mainModal = registry.get('index=' + this.source.mainModal);
                    //reset grid
                    grid = registry.get('index= '+ this.source.modalGrid);
                    grid.destroyInserted();
                    //reset form
                    this.getModalForm().set('visible', false);
                    //close modal
                    mainModal.closeModal();
                    //reload sub listing
                    subProductListing = registry.get('index=' + this.source.subProductListing);
                    subProductListing.source.set('params.t ', Date.now());
                }
            },

            /**
             *
             * @param action
             * @param id
             */
            setProductId: function (action, id) {

                this.getModalForm().configurableData = {
                    product_id: id
                };

                this.openConfigurableModal();
                // Add here logic when we have qty set by merchant
                // this.renderForm();
            },

            /**
             * Updates Configurable data and renders modal form
             */
            setConfigurableData: function () {
                var data;

                data = registry.get('index=' + this.source.configurableForm).source.data;

                this.getModalForm().configurableData = $.extend(this.getModalForm().configurableData, data);

                this.updateModalGrid();

                this.setAdditionalData(this.getModalForm().configurableData);

                this.renderForm(this.getModalForm(), this.getModalForm().configurableData);
            },

            /**
             * Updates Modal grid action label and "qty" column
             */
            updateModalGrid: function () {
                var rowIndex,
                    grid,
                    productId;

                productId = this.getModalForm().configurableData.product_id;

                grid = registry.get('index='+ this.source.modalGrid);

                _.each(grid.externalSource().data.items, function (item, key) {
                    if (item.entity_id === productId){
                        rowIndex = key;
                    }else {
                        grid.externalSource().set('data.items.' + key + '.input_qty', null);

                        if (item.type_id === 'configurable'){
                            grid.externalSource().set('data.items.' + key + '.actions.view.label', '[' + $.mage.__('Configure & Add') + ']');
                        }else {
                            grid.externalSource().set('data.items.' + key + '.actions.view.label', '[' + $.mage.__('Add') + ']');
                        }

                    }
                });

                if (rowIndex !== undefined){
                    grid.externalSource().set('data.items.' + rowIndex + '.input_qty', this.getModalForm().configurableData.qty);
                    grid.externalSource().set('data.items.' + rowIndex + '.actions.view.label', '[' + $.mage.__('Change') + ']');
                }
            },

            /**
             * Finds and returns in uiRegistry Configurable modal window
             */
            getConfigurableModal: function () {
                if (!this.configurableModal){
                    this.configurableModal = registry.get('index=' + this.source.configurableModal);
                }

                return this.configurableModal;
            },

            getModalForm: function () {
                if (!this.modalForm){
                    this.modalForm = registry.get('index=' + this.source.insertForm);
                }

                return this.modalForm;
            },

            /**
             * Opens Configurable modal window and renders form
             */
            openConfigurableModal: function () {
                var configurableForm = registry.get('index=' + this.source.insertConfigurableForm);
                this.getConfigurableModal().openModal();
                this.renderForm(configurableForm, []);
            },

            /**
             * Render form data.
             */
            renderForm: function (form, params) {
                form.set('visible', true);
                form.destroyInserted();
                form.render(params);
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
                    this.setAdditionalData(this.getModalForm().configurableData);

                    this.setAdditionalData(data).submit(redirect);
                }
            }
        });
    }
);
