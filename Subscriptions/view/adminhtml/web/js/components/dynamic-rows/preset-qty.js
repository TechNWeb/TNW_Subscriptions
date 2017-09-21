/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            imports: {
                updateValidation: 'index = tnw_subscr_unlock_preset_qty:checked',
                disabled: '!index = tnw_subscr_unlock_preset_qty:checked'
            }
        },

        /**
         * Updates field validators.
         *
         * @param {boolean} presetQtyFlag
         * @return {void}
         */
        updateValidation: function(presetQtyFlag) {
            this.setValidation('required-entry', presetQtyFlag);
        }
    });
});
