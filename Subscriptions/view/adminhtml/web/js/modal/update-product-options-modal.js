/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/modal/modal-component',
    'uiRegistry'
], function (ModalComponent, registry) {
    'use strict';

    return ModalComponent.extend({
        /**
         * update product options on product form and save form.
         */
        updateProductOptions: function() {
            var data,
                itemId,
                subId,
                formName,
                currentItemData,
                form,
                productOptionsData = registry.get('index=' + this.source.editOptionsForm).source.data
                ;

            if (typeof productOptionsData != 'undefined'
                && typeof productOptionsData.super_attribute != 'undefined'
                && typeof productOptionsData.item_data != 'undefined'
            ) {
                subId = productOptionsData.item_data.quote_id;
                itemId = productOptionsData.item_data.item_id;

                if (itemId && subId) {
                    formName = this.parentName + '.container_' + subId + '.container_item_' + itemId + '.form';
                    form = registry.get('name=' + formName);
                }

                if (form
                    && itemId
                    && typeof form.source.data['item_' + itemId] != 'undefined'
                ) {
                    currentItemData = form.source.data['item_' + itemId];
                    currentItemData['super_attribute'] = productOptionsData.super_attribute;

                    data = {
                        'subscription_profile_id': productOptionsData.item_data.quote_id,
                        'sub_product_id': itemId
                    };
                }
                form.save('', data);
            }
        }
    });
});
