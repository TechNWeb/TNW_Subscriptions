/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/fieldset'
], function (Fieldset) {
    'use strict';

    return Fieldset.extend({

        initialize: function () {
            this._super();

            if (this.imports && this.imports.subscriptionNumber) {
                this.label += ' (' + this.imports.subscriptionNumber + ')?';
            } else {
                this.label += '?';
            }

            return this;
        }
    });
});
