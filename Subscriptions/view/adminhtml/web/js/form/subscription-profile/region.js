/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
        /**
         * Creates input from template, renders it via renderer.
         *
         * @returns {Object} Chainable.
         */
        initInput: function () {
            layout([utils.template(inputNode, this)]);

            return this;
        },

        checkValidation: function (checkedSame) {
            this.setValidation('required-entry', checkedSame);
        },

        checkVisibility: function () {
            var customerAddressId = registry.get('index=customer_address_id');
            var options = this.options();
            var optionsLength = 0;
            if (typeof options == 'Array') {
                optionsLength = options.length;
            }
            if (!customerAddressId.visible() && optionsLength > 0) {
                this.setVisible(true);
            }
        }

    });
});

