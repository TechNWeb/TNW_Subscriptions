/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
], function (Abstract, registry) {
    'use strict';

    return Abstract.extend({
        defaults: {
            imports: {
                updateValidation: 'index = tnw_subscr_unlock_preset_qty:checked',
                setDisabled: '!index = tnw_subscr_unlock_preset_qty:checked',
                updateDisabledField: 'index = tnw_subscr_unlock_preset_qty:checked',
            },
            oldPresetQtyValue : ''
        },
        /**
         * If field "Preset qty" is disabled and equals 0 - hide 0
         * else if value is empty set old value
         *
         * @param {boolean} isEditable
         */
        updateDisabledField: function (isEditable) {
            var value = this.getInitialValue();
            if (value == 0 && this.disabled() && !isEditable) {
                this.oldPresetQtyValue = value;
                this.value('');
            } else if (this.value() == '') {
                this.value(this.oldPresetQtyValue);
            }
        },

        /**
         * Updates field validators.
         *
         * @param {boolean} presetQtyFlag
         * @return {void}
         */
        updateValidation: function (presetQtyFlag) {
            this.setValidation('required-entry', presetQtyFlag);
            this.setValidation('validate-greater-than-zero', presetQtyFlag);
        },

        setDisabled: function (value) {
            if (registry.get('dataScope = ' + this.parentScope + ',index = container_option') !== undefined) {
                if (registry.get('dataScope = ' + this.parentScope + ',index = container_option').disabled) {
                    return this.disabled(true)
                }
            }
            this.disabled(value)
        }
    });
});
