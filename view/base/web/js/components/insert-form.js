/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/insert',
    'mageUtils',
    'jquery',
    'uiRegistry'
], function (Insert, utils, $, registry) {
    'use strict';

    /**
     * Get page actions element.
     *
     * @param {String} elem
     * @param {String} actionsClass
     * @returns {String}
     */
    function getPageActions(elem, actionsClass) {
        var el = document.createElement('div');

        el.innerHTML = elem;

        return el.getElementsByClassName(actionsClass)[0];
    }

    /**
     * Return element without page actions toolbar
     *
     * @param {String} elem
     * @param {String} actionsClass
     * @returns {String}
     */
    function removePageActions(elem, actionsClass) {
        var el = document.createElement('div'),
            actions;

        el.innerHTML = elem;
        actions = el.getElementsByClassName(actionsClass)[0];
        if (actions && actions !== undefined){
            el.removeChild(actions);
        }

        return el.innerHTML;
    }

    return Insert.extend({
        defaults: {
            configurableData: {},
            externalFormName: '${ $.ns }.${ $.ns }',
            template: 'TNW_Subscriptions/form/insert',
            pageActionsClass: 'page-actions',
            actionsContainerClass: 'page-main-actions',
            cssclass: '',
            exports: {
                prefix: '${ $.externalFormName }:selectorPrefix'
            },
            imports: {
                toolbarSection: '${ $.toolbarContainer }:toolbarSection',
                prefix: '${ $.toolbarContainer }:rootSelector',
                messagesClass: '${ $.externalFormName }:messagesClass'
            },
            listens: {
                '${ $.externalFormName }:responseData':'handleResponse'
            },
            settings: {
                ajax: {
                    ajaxSave: true,
                    exports: {
                        ajaxSave: '${ $.externalFormName }:ajaxSave'
                    },
                    imports: {
                        responseStatus: '${ $.externalFormName }:responseStatus',
                        responseData: '${ $.externalFormName }:responseData'
                    }
                }
            },
            modules: {
                externalForm: '${ $.externalFormName }'
            }
        },

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe(['responseStatus', 'cssclass']);
        },

        /** @inheritdoc */
        initConfig: function (config) {
            var defaults = this.constructor.defaults;

            utils.extend(defaults, defaults.settings[config.formSubmitType] || {});

            return this._super();
        },

        /** @inheritdoc*/
        destroyInserted: function () {
            if (this.isRendered && this.getExternalForm()) {
                this.getExternalForm().delegate('destroy');
                this.removeActions();
                this.responseStatus(undefined);
                this.responseData = {};
            }

            return this._super();
        },

        getExternalForm: function () {
            return registry.get(this.externalFormName);
        },

        /** @inheritdoc */
        onRender: function (data) {
            var actions = getPageActions(data, this.pageActionsClass);

            if (!data.length) {
                return this;
            }
            data = removePageActions(data, this.pageActionsClass);
            this.renderActions(actions);
            this._super(data);
        },

        /**
         * Insert actions in toolbar.
         *
         * @param {String} actions
         */
        renderActions: function (actions) {
            var $container = $('<div/>');

            $container
                .addClass(this.actionsContainerClass)
                .append(actions);

            this.formHeader = $container;

            $(this.toolbarSection).append(this.formHeader);
        },

        /**
         * Remove actions toolbar.
         */
        removeActions: function () {
            $(this.formHeader).siblings('.' + this.messagesClass).remove();
            $(this.formHeader).remove();
            this.formHeader = $();
        },

        /**
         * Reset external form data.
         */
        resetForm: function () {
            if (this.externalSource()) {
                this.externalSource().trigger('data.reset');
                this.responseStatus(undefined);
            }
        },

        /**
         * Redirect if ajaxRedirect is set in response
         * @param response
         */
        handleResponse: function (response) {
            if (response.ajaxRedirect) {
                window.location = response.ajaxRedirect;
            }
        }
    });
});
