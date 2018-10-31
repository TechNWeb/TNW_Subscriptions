/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/form/components/button'
], function (_, Button) {
    'use strict';

    return Button.extend({
        defaults: {
            displayPrimary: false,
            subButtonLeft: false,
            subButtonRight: false
        },

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe([
                    'visible',
                    'disabled',
                    'title',
                    'displayPrimary',
                    'subButtonLeft',
                    'subButtonRight'
                ]);
        }
    });
});
