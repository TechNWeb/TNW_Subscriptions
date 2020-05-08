/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/components/field/preview-field',
    'jquery',
    'mage/translate'
], function (Component, $j) {
    'use strict';

    return Component.extend({
        defaults: {
            previewLabelOnce: ''
        },
        /**
         * Sets complete preview label.
         *
         * @param value
         */
        setCompletePreviewLabel: function (value) {
            if (parseInt(value) === 0) {
                this.completePreviewLabel($j.mage.__(this.previewLabelComplete));
                return;
            }
            var label = (parseInt(value) === 1)
                ? $j.mage.__(this.previewLabelOnce)
                : $j.mage.__(this.previewLabel).replace('%s', value);
            this.completePreviewLabel(label);
        },

        onTermChange: function (value) {
            var result = this.visibleOnEdit ? !parseInt(value) : false;
            this.visible(result);
        }
    });
});
