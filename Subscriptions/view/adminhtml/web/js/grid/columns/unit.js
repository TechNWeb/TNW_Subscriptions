/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/grid/columns/select',
    'jquery'
], function (_, Select, $) {
    'use strict';

    return Select.extend({
        defaults: {
            plural: "s"
        },

        /**
         * {@inheritdoc}
         */
        getLabel: function (row) {
            var label = this._super();

            if (row && row.frequency != 1) {
                label = label + this.plural;
            }

            return $.mage.__(label);
        }
    });
});
