/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/lib/step-wizard',
    'uiRegistry',
    'underscore'
], function (StepWizard, uiRegistry, _) {
    return StepWizard.extend({

        defaults: {
            modalComponent: null
        },

        initialize: function () {
            var self = this;
            this._super();
            uiRegistry.async(this.parentName)(function (modal) {
                self.modalComponent = modal;
            });
        },

        close: function () {
            uiRegistry.get('index = recurring_options').setDefaultRecords();
            this.modalComponent.closeModal();
        },

        /**
         * Cancel & close wizard with modal.
         */
        cancel: function () {
            uiRegistry.get('index = recurring_options').restoreToDefault();
            this.modalComponent.setPrevValues(this);
            this.wizard.cleanErrorNotificationMessage();
            this.wizard.cleanNotificationMessage();
            this.wizard.index = 0;
            this.selectedStep(this.stepsNames.first());
            this.close();
        },

        isSelectedStep: function (name) {
            return this.selectedStep() === name;
        }
    });
});
