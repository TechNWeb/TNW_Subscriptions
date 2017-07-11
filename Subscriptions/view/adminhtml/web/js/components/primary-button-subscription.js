/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/components/button',
    'jquery'
], function (_, Button, $j) {
    'use strict';

    return Button.extend({
        defaults: {
            displayPrimary: true
        },

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe([
                    'disabled',
                    'displayPrimary'
                ]);
        },

        /**
         * @inheritdoc
         */
        action: function () {
            $j('#save').click();
        }
    });
});
