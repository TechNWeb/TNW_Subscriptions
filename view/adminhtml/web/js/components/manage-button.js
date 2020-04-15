/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/form/components/button',
    'uiRegistry'
], function (_, Button, registry) {
    return Button.extend({

        /**
         * Disable button if one-time purchase selected.
         * @param typeValue
         * @returns {boolean}
         */
        onChangedPurchaseType: function (typeValue) {
            if (typeValue === '1') {
                this.disabled(true);
                return false;
            }
            this.disabled(false);
        }
    });
});
