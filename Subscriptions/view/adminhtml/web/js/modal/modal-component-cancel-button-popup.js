/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/modal/modal-component',
    'jquery'
], function (ModalComponent, $j) {
    'use strict';

    return ModalComponent.extend({
        /**
         * {@inheritdoc}
         */
        initModal: function () {
            this._super();

            $j('.tnw_subscriptionprofile_cancel_button_popup .modal-header').hide();
        }
    });
});
