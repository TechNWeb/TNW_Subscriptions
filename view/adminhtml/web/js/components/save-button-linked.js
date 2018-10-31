/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
            displayPrimary: true,
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
        },

        /**
         * {@inheritdoc}
         */
        action: function () {
            $j('.page-actions .page-actions-buttons #save').click();
        }
    });
});
