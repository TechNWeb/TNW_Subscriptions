/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/checkbox-set'
], function (CheckboxSet) {
    return CheckboxSet.extend({

        /**
         * Show if place on hold
         * @param status
         */
        handleVisibility: function (status) {
            this.visible(status === 4);
        }
    })
});
