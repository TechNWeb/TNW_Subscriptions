/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'ko',
        'uiComponent'
    ],
    function ($,
              ko,
              Component) {
        return Component.extend({
            items: {},

            /**
             * @inheritdoc
             */
            initialize: function () {
                this._super()
                    ._initItems();
            },

            /**
             * Init subscription plan items observables
             *
             * @returns {Class}
             * @private
             */
            _initItems: function () {
                this.items = [];

                return this;
            }
        });
    }
);
