/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            data: []
        },

        /**
         * {@inheritdoc}
         */
        setInitialValue: function () {
            this._super();
            this.data = typeof this.imports.messageHistoryData == "undefined" ? [] : this.imports.messageHistoryData;

            return this;
        }
    });
});
