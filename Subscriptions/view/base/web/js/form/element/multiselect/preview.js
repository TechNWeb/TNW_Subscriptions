/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define(['underscore'], function(_) {
    'use strict';

    return function(MultiSelect) {
        return MultiSelect.extend({
            /**
             * Processes preview for option by it's value, and sets the result
             * to 'preview' observable
             *
             * @returns {Object} Chainable.
             */
            getPreview: function () {
                var preview = _.map(this.normalizeData(this.value()), function(value) {
                    var option = this.indexedOptions[value];
                    return option ? option.label : ''
                }.bind(this)).join(', ');

                this.preview(preview);
                return preview;
            }
        });
    };
});
