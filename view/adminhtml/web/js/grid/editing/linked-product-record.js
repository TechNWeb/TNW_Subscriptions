/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/grid/editing/record',
], function (Record) {
    return Record.extend({
        defaults: {
            templates: {
                fields: {
                    price: {
                        component: 'TNW_Subscriptions/js/grid/editing/element/disableable_field',
                        template: 'ui/form/element/input',
                        imports: {
                            disabled: false,
                            setDisabledPrice: '${ $.provider }:data.lock_product_price',
                        }
                    },
                    preset_qty: {
                        component: 'TNW_Subscriptions/js/grid/editing/element/disableable_field',
                        template: 'ui/form/element/input',
                        imports: {
                            disabled: false,
                            setDisabledPresetQty: '${ $.provider }:data.unlock_preset_qty',
                        }
                    }
                }
            }
        }
    })
})
