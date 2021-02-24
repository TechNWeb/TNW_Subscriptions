/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/fieldset',
    'underscore'
], function (Fieldset, _) {
    return Fieldset.extend({
        defaults: {
            tracks: {
                disabled: true
            }
        },

        setDisabled: function (disabledFrequencies) {
            if (_.contains(disabledFrequencies, this.source.get(this.dataScope + '.billing_frequency_id'))) {
                this.disabled = true;
            }
        }
    })
})
