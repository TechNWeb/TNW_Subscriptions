/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry'
], function (Abstract, uiRegistry) {
    'use strict';

    return Abstract.extend({

        /**
         * {@inheritdoc}
         */
        closePopup: function () {
            uiRegistry.get('index=cancelModal').closeModal();

            return this;
        }
    });
});
