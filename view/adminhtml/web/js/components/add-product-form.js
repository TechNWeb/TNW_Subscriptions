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
                imports: {
                    refreshProductQty: 'ns = ${ $.ns }, index = billing_frequency:value'
                },
                listens: {
                    responseStatus: 'processResponseStatus'
                },
                configureTypes: ['configurable']
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
             * Refresh product qty in modal grid.
             *
             * @param {string|number} value
             * @returns {void}
             */
            refreshProductQty: function (value) {
                var rowIndex,
                    grid,
                    modalForm = this.getModalForm(),
                    productId;

                productId = (
                    modalForm.configurableData != undefined &&
                    modalForm.configurableData.hasOwnProperty('product_id')
                ) ? modalForm.configurableData.product_id : null;

                if (!productId) {
                    return;
                }

                if (this.isConfigureRequired(productId)) {
                    return;
                }

                grid = this.getModalGrid();

                this.resetQtyAndActions();
                rowIndex = this.getProductRowIndex(productId);

                if (rowIndex !== undefined) {

                    var frequencyPrices = this.source.data.product_frequencies;
                    if (frequencyPrices && value && frequencyPrices[value]) {

                        grid.externalSource().set(
                            'data.items.' + rowIndex + '.input_qty',
                            frequencyPrices[value].preset_qty
                        );
                        grid.externalSource().set(
                            'data.items.' + rowIndex + '.actions.view.label',
                            '[' + $.mage.__('Change') + ']'
                        );
                    }

                    // Update current qty for billing frequency.
                    modalForm.configurableData.qty = frequencyPrices[value].preset_qty;
                }
            },

            /**
             * Run specified action for product.
             *
             * @param action
             * @param id
             * @return {void}
             */
            setProductId: function (action, id) {
                this.getModalForm().configurableData = {
                    product_id: id
                };

                if (this.isConfigureRequired(id)) {
                    this.openConfigurableModal(id);
                } else {
                    this.renderForm(this.getModalForm(), this.getModalForm().configurableData);
                }
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
             * Returns modal grid instance.
             *
             * @returns {*}
             */
            getModalGrid: function () {
                return registry.get('index=' + this.source.modalGrid);
            },

            /**
             * Returns row index by product Id.
             *
             * @param {string|number} productId
             * @returns {*}
             */
            getProductRowIndex: function (productId) {
                var rowIndex,
                    grid;

                grid = this.getModalGrid();

                _.each(grid.externalSource().data.items, function (item, key) {
                    if (item.entity_id === productId) {
                        rowIndex = key;
                    }
                });

                return rowIndex;
            },

            /**
             * Reset "Qty" and "Action" columns.
             *
             * @return {void}
             */
            resetQtyAndActions: function () {
                var grid = this.getModalGrid();

                _.each(grid.externalSource().data.items, function (item, key) {
                    grid.externalSource().set('data.items.' + key + '.input_qty', null);

                    if (item.type_id === 'configurable') {
                        grid.externalSource().set(
                            'data.items.' + key + '.actions.view.label',
                            '[' + $.mage.__('Configure & Add') + ']'
                        );
                    } else {
                        grid.externalSource().set(
                            'data.items.' + key + '.actions.view.label',
                            '[' + $.mage.__('Add') + ']'
                        );
                    }
                });
            },

            /**
             * Updates Modal grid action label and "qty" column
             * 
             * @return {void}
             */
            updateModalGrid: function () {
                var rowIndex,
                    grid,
                    productId;

                productId = this.getModalForm().configurableData.product_id;
                grid = this.getModalGrid();

                this.resetQtyAndActions();
                rowIndex = this.getProductRowIndex(productId);

                if (rowIndex !== undefined){
                    grid.externalSource().set(
                        'data.items.' + rowIndex + '.input_qty',
                        this.getModalForm().configurableData.subscribe_qty
                    );
                    grid.externalSource().set(
                        'data.items.' + rowIndex + '.actions.view.label',
                        '[' + $.mage.__('Change') + ']'
                    );
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
             *
             * @param {string|number} product_id
             * @return {void}
             */
            openConfigurableModal: function (product_id) {
                var configurableForm = registry.get('index=' + this.source.insertConfigurableForm),
                    params = {'product_id' : product_id};
                this.getConfigurableModal().openModal();
                this.renderForm(configurableForm, params);
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
             * Disable default save
             */
            save: function (redirect, data) {
            },

            /**
             * Validate and save form.
             */
            ajaxSubmit: function () {
                this.validate();

                if (!this.additionalInvalid && !this.source.get('params.invalid')) {
                    this.setAdditionalData(this.getModalForm().configurableData);

                    this.submit();
                }
            },

            /**
             * Is configuration form should be displayed or not?
             *
             * @param {string|number} productId
             * @return {boolean}
             */
            isConfigureRequired: function (productId) {
                var grid = registry.get('index='+ this.source.modalGrid),
                    items = grid.externalSource().data.items,
                    result = false;

                for (var index in items) {
                    var product = items[index];
                    if (product.hasOwnProperty('entity_id') && product.entity_id == productId) {
                        result = (
                            product.hasOwnProperty('tnw_subscr_unlock_preset_qty') &&
                            product.tnw_subscr_unlock_preset_qty == 1 &&
                            this.configureTypes.indexOf(product.type_id) == -1
                        );
                        break;
                    }
                }

                return !result;
            }
        });
    }
);
