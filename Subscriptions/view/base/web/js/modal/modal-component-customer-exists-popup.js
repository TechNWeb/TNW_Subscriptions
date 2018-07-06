/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/modal/modal-component',
    'uiRegistry',
    'jquery'
], function (ModalComponent, uiRegistry, $j) {
    'use strict';

    return ModalComponent.extend({
        /**
         * Close customer exists popup.
         */
        closePopup: function () {
            uiRegistry.get('index=email').clear();

            this.closeModal();
        },

        /**
         * {@inheritdoc}
         */
        initModal: function () {
            this._super();

            $j('.customer_exists_popup .modal-header').hide();
        }
    });
});
