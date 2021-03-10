/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-field',
    'jquery',
    'underscore',
    'mage/translate'
], function (Component, $j, _) {
    'use strict';

    return Component.extend({

        /**
         * Sets complete preview label.
         *
         * @param value
         */
        setCompletePreviewLabel: function (value) {
            var label,
                indexedOptions = _.indexBy(this.options, 'value');

            if (indexedOptions[value] !== undefined) {
                label = indexedOptions[value].label;
            } else {
                label = this.source.get('data.billing_frequency_label') ?? value;
            }
            this.completePreviewLabel(label);
        }
    });
});
