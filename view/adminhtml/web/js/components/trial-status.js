/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/single-checkbox'
], function (SingleCheckbox) {
    return SingleCheckbox.extend({
        /**
         * If is bundle product with dynamic price, disable trial
         * Until decision, disable trial for bundle completely
         * @param priceType
         */
        onPriceTypeChange: function (priceType) {
            this.value('0');
        }
    })
})
