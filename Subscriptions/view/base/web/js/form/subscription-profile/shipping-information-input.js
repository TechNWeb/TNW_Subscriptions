/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry'
], function (Abstract, registry) {
    'use strict';

    return Abstract.extend({
        defaults: {
            addressFieldsetIndex: ''
        },

        /** @inheritdoc */
        onUpdate: function () {
            this._super();
            var addressFieldset = registry.get('index = ' + this.addressFieldsetIndex);
            if (this.valueChangedByUser && addressFieldset && typeof addressFieldset.openAddressForm === 'function') {
                addressFieldset.openAddressForm(true);
            }
        }
    });
});
