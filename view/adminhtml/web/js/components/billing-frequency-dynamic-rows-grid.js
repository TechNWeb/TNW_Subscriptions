/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (DynamicRows, alert, $t) {
    return DynamicRows.extend({

        deleteRecord: function (index, recordId) {
            var recordInstance = _.find(this.elems(), function (elem) {
                return elem.index === index;
            });

            if (recordInstance.data().grid_url !== undefined
                && recordInstance.data().grid_url !== ""
                && recordInstance.data().grid_url !== null
            ) {
                alert({
                    content: $t('Cannot unlink product from Billing frequency. '
                        + 'Some <a href="%1" target="_blank">subscription profiles</a> use it.')
                        .replace('%1', recordInstance.data().grid_url)
                });
                return false;
            }
            this._super(index, recordId);
        },

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
