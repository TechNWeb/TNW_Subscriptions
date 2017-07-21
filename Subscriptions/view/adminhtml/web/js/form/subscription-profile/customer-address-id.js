/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function (Abstract, registry) {
    'use strict';

    return Abstract.extend({
        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();

            var infoFieldSet = registry.get('index=' + this.infoFieldSet);
            var elemsForUpdate = infoFieldSet.elems();
            var currentValue = this.value();
            var infoAttributesData = this.addressesData[currentValue];

            if (typeof infoAttributesData != 'undefined') {
                elemsForUpdate.forEach(function(element, i, arr) {
                    var index = element.index;
                    var value = '';
                    if (typeof infoAttributesData[index] != 'undefined') {
                        value = infoAttributesData[index];
                    }
                    element.value(value);
                });
            }

        }
    });
});
