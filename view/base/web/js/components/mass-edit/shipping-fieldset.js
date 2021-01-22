/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
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
            var parentForm = uiRegistry.get(this.ns + '.' + this.ns);
            parentForm.validate();
            if (this._isChildrenHasErrors(false, this)) {
                throw new Error($t('Please check fields below.'));
            }
        },

        back: function () {
        }
    });
});
