/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/columns/select',
    'uiRegistry'
], function (_, Select, uiRegistry) {
    'use strict';

    return Select.extend({

        /**
         * {@inheritdoc}
         */
        getLabel: function () {
            var label = this._super();

            var uiFrequency = uiRegistry.get('index=frequency');

            if (uiFrequency.containers[0].source.data.items) {
                debugger;
            }
            return label + 's';
        }
    });
});
