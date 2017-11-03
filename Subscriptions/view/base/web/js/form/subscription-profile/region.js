/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/region',
    'uiLayout',
    'mageUtils',
    'uiRegistry'
], function (Select, layout, utils, registry) {
    'use strict';

    var inputNode = {
        parent: '${ $.$data.parentName }',
        component: 'Magento_Ui/js/form/element/abstract',
        template: '${ $.$data.template }',
        provider: '${ $.$data.provider }',
        name: '${ $.$data.index }_input',
        dataScope: '${ $.$data.customEntry }',
        customScope: '${ $.$data.customScope }',
        sortOrder: '${ $.$data.sortOrder }',
        displayArea: 'body',
        label: '${ $.$data.label }',
        placeholder: '${ $.$data.placeholder }',
        additionalClasses: '${ $.$data.additionalClass }'
    };

    return Select.extend({

        defaults:{
            customerAddressSelector: 'customer_address_id',
            listens: {
                '${ $.parentName }:visible': 'checkVisibility',
                '${ $.provider }:data.validate': 'validate'
            }
        },

        /**
         * Creates input from template, renders it via renderer.
         *
         * @returns {Object} Chainable.
         */
        initInput: function () {
            layout([utils.template(inputNode, this)]);

            return this;
        },

        /**
         * Checks field "required" validation flag.
         *
         * @param {boolean} checkedSame
         * @return void
         */
        checkValidation: function (checkedSame) {
            this.setValidation('required-entry', checkedSame);
        },

        /**
         * Checks field visibility.
         *
         * @return void
         */
        checkVisibility: function () {
            var customerAddressId = registry.get('index=' + this.customerAddressSelector),
                country = registry.get(this.parentName + '.' + 'country_id'),
                options = this.options(),
                optionsLength = 0;

            // hide select and corresponding text input field if country editor invisible.
            if (country && !country.visible()) {
                this.setVisible(false);

                if (this.customEntry) {
                    this.toggleInput(false);
                }
            } else if (options instanceof Array) {
                    optionsLength = options.length;

                if (!customerAddressId.visible() && optionsLength > 0) {
                    this.setVisible(true);
                }
            }
        }
    });
});
