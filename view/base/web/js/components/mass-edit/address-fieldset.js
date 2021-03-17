/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'jquery',
    'mage/translate'
], function (Collection, uiRegistry, $, $t) {
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
            var parentForm = uiRegistry.get(this.ns + '.' + this.ns);

            parentForm.validate();
            if (this._isChildrenHasErrors(false, this)) {
                throw new Error($t('Please check fields below.'));
            }
        },

        back: function () {
        },

        setVisible: function (fieldsetName) {
            var visible = this.name === fieldsetName,
                existingBillingFieldset = this.getChild('existing_billing');
            this.visible(visible);
            if (existingBillingFieldset) {
                existingBillingFieldset.visible(
                    visible && this.source.get('data.billing_address.same_as_shipping') === '0'
                );
            }
        }
    });
});
