/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry'
], function (Abstract, uiRegistry) {
    'use strict';

    return Abstract.extend({
        /**
         * Sets initial value of the element and subscribes to it's changes.
         */
        setInitialValue: function () {
            this._super();
            this.isUnlockPresetQty();

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.isUnlockPresetQty();
        },

        /**
         * Enable or disable preset qty field.
         */
        isUnlockPresetQty : function () {
            var index = this.inputName.replace(/[^\d.]/g, '');

            var unlock = uiRegistry.get('index=linked').source.data.links.linked[index].tnw_subscr_unlock_preset_qty;

            if (!unlock || unlock == '0') {
                this.disable()
            } else {
                this.enable();
            }
        }
    });
});
