/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/modal/modal-component',
    'uiRegistry'
], function (ModalComponent, uiRegistry) {
    'use strict';

    return ModalComponent.extend({
       closePopup: function () {
           uiRegistry.get('index=email').clear();

           this.closeModal();
       }
    });
});
