/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'underscore',
    'mageUtils',
    'uiLayout',
    'uiElement'
], function (_, utils, layout, Element) {
    'use strict';

    return Element.extend({
        defaults: {
            visible: true,
            preview: '',
            focused: false,
            required: false,
            disabled: false,
            valueChangedByUser: false,
            elementTmpl: 'ui/form/element/input',
            tooltipTpl: 'ui/form/element/helper/tooltip',
            fallbackResetTpl: 'ui/form/element/helper/fallback-reset',
            'input_type': 'input',
            placeholder: '',
            description: '',
            labelVisible: true,
            label: '',
            error: '',
            warn: '',
            notice: '',
            customScope: '',
            default: '',
            isDifferedFromDefault: false,
            showFallbackReset: false,
            setByMerchant: '',
            valueUpdate: false, // ko binding valueUpdate

            switcherConfig: {
                component: 'Magento_Ui/js/form/switcher',
                name: '${ $.name }_switcher',
                target: '${ $.name }',
                property: 'value'
            },
            listens: {
                'setByMerchant': 'toggleSetByMerchant'
            },

            links: {
                value: '${ $.provider }:${ $.dataScope }'
            }
        },

        /**
         * Invokes initialize method of parent class,
         * contains initialization logic
         */
        initialize: function () {
            _.bindAll(this, 'reset');

            this._super()
                .setInitialValue()
                ;

            return this;
        },

        /**
         * Initializes observable properties of instance
         *
         * @returns {Abstract} Chainable.
         */
        initObservable: function () {
            var rules = this.validation = this.validation || {};

            this._super();

            this.observe('error disabled focused preview visible value warn isDifferedFromDefault')
                .observe('setByMerchant')
                .observe({
                    'required': !!rules['required-entry']
                });

            return this;
        },

        /**
         * Initializes regular properties of instance.
         *
         * @returns {Abstract} Chainable.
         */
        initConfig: function () {
            var uid = utils.uniqueid(),
                name,
                valueUpdate,
                scope;

            this._super();

            scope = this.dataScope;
            name = scope.split('.').slice(1);

            valueUpdate = this.showFallbackReset ? 'afterkeydown' : this.valueUpdate;

            _.extend(this, {
                uid: uid,
                noticeId: 'notice-' + uid,
                inputName: utils.serializeName(name.join('.')),
                valueUpdate: valueUpdate
            });

            return this;
        },

        /**
         * Sets initial value of the element and subscribes to it's changes.
         *
         * @returns {Abstract} Chainable.
         */
        setInitialValue: function () {
            this.initialValue = this.getInitialValue();

            if (this.value.peek() !== this.initialValue) {
                this.value(this.initialValue);
            }

            this.on('value', this.onUpdate.bind(this));

            if (this.initialValue == '') {
                //if we have new value or we have zero we make checkbox inactive.
                this.toggleSetByMerchant(false);
            } else {
                //if we have some value we set checkbox true.
                this.setByMerchant(this.disabled(true));
            }

            return this;
        },

        /**
         * Gets initial value of element
         *
         * @returns {*} Elements' value.
         */
        getInitialValue: function () {
            var values = [this.value(), this.default],
                value;
            values.some(function (v) {
                if (v !== null && v !== undefined) {
                    value = v;
                    return true;
                }
                return false;
            });

            return this.normalizeData(value);
        },

        /**
         * Checks if element has addons
         *
         * @returns {Boolean}
         */
        hasAddons: function () {
            return this.addbefore || this.addafter;
        },

        /**
         * Checks if element has service setting
         *
         * @returns {Boolean}
         */
        hasService: function () {
            return this.service && this.service.template;
        },

        /**
         * Sets value observable to initialValue property.
         *
         * @returns {Abstract} Chainable.
         */
        reset: function () {
            this.value(this.initialValue);
            this.error(false);

            return this;
        },

        /**
         * Converts values like 'null' or 'undefined' to an empty string.
         *
         * @param {*} value - Value to be processed.
         * @returns {*}
         */
        normalizeData: function (value) {
            return utils.isEmpty(value) ? '' : value;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this.bubble('update', this.hasChanged());

            this.validate();
        },

        /**
         * @param {Boolean} state
         */
        toggleSetByMerchant: function (state) {
            this.disabled(!state);
        },

        /**
         *  Callback when value is changed by user
         */
        userChanges: function () {
            this.valueChangedByUser = true;
        },

        /**
         * Set index for checkbox.
         */
        setByMerchantIndex: function () {
            return this.inputName.replace(/[^\d.]/g, '');
        }
    });
});
