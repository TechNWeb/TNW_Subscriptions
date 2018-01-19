/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/insert-listing'
], function (InsertListing) {
    'use strict';

    return InsertListing.extend({
        /**
         * Reloads history listing.
         *
         * @param {bool} value
         */
        forceRender: function (value) {
            if (value) {
                if (this.isRendered) {
                    this.reload();
                } else {
                    this.render();
                }
            }
        }
    });
});
