/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/button',
    'uiRegistry'
], function (Element, registry) {
    'use strict';

    return Element.extend({
        defaults:{
            customerAddressSelector: 'customer_address_id'
        },

        checkVisibility: function () {
            var visible = true;
            var addressSelect = registry.get('index=' + this.customerAddressSelector);

            if (addressSelect.visible() || !addressSelect.issetShippingAddress) {
                visible = false;
            }

            this.visible(visible);
        }
    });
});
