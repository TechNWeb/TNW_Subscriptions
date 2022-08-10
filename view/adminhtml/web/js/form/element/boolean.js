/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/single-checkbox',
    'mage/translate'
], function (Abstract, $t) {
    'use strict';

    return Abstract.extend({
        /**
         * Returns unwrapped preview observable.
         *
         * @returns {String} Value of the preview observable.
         */
        getPreview: function () {
            return this.value() === '0' ? $t('No') : $t('Yes');
        },
    });
});
