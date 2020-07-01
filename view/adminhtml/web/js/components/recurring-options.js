/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'underscore',
    'mage/translate'
], function (Collection, uiRegistry, $t) {
    'use strict';

    return Collection.extend({
        defaults: {
            notificationMessage: {
                text: null,
                error: null
            }
        },

        render: function (wizard) {
            this.wizard = wizard;
        },

        force: function (wizard) {
            var recurringComponent = uiRegistry.get('index = recurring_options'),
                activeRecords = _.filter(recurringComponent.recordData(), function (elem) {
                    return elem && elem['is_delete'] !== '1';
                }, this);
            if (!activeRecords.length) {
                throw new Error($t('Please add billing frequency.'));
            }
            uiRegistry.get('product_form.product_form').validate();
            if (this._isChildrenHasErrors(false, this)) {
                throw new Error($t('Please check fields below.'));
            }
            recurringComponent.processNewRecords();
        },

        back: function () {
        }
    });
});
