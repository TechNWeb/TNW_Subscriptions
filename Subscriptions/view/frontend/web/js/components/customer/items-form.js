/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'TNW_Subscriptions/js/components/modify-subscriptions-form',
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({

            /**
             * Hide loader for this form or for parent element.
             *
             * @returns {Object}
             */
            hideLoader: function () {
                $('body').trigger('processStop');

                return this;
            }
        });
    }
);
