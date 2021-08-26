/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    return Abstract.extend({
        setDisabledPrice: function (lockPrice) {
            lockPrice = lockPrice === '1';
            this.disabled(lockPrice);
        },

        setDisabledPresetQty: function (presetQty) {
            presetQty = presetQty === '1';
            this.disabled(!presetQty);
        }
    })
})
