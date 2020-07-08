/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/dynamic-rows/dynamic-rows-grid'
], function (DynamicRows) {
    return DynamicRows.extend({

        /**
         * Handle grid pager
         */
        reload: function () {
            this.pageSize = parseInt(this.pageSize);
            this._super();
            this.parsePagesData(this.recordData());
            this.currentPage(1);
        }

    });
});
