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
            Array.prototype.first = function () {return this[0]};
            this._super();
            uiRegistry.async(this.parentName)(function (modal) {
                self.modalComponent = modal;
            });
        },

        close: function () {
            this.modalComponent.closeModal();
        },

        /**
         * Cancel & close wizard with modal.
         */
        cancel: function () {
            this.modalComponent.setPrevValues(this);
            this.wizard.cleanErrorNotificationMessage();
            this.wizard.cleanNotificationMessage();
            this.wizard.index = 0;
            this.selectedStep(this.stepsNames.first());
            this.close();
        },

        next: function () {
            var parentForm = uiRegistry.get(this.ns + '.' + this.ns);
            if (this.selectedStep() === this.name + '.payment_method') {
                parentForm.beforeSubmit();
            } else if (this.selectedStep() === this.name + '.shipping_address') {
                parentForm.submitShippingAddress();
            } else if (this.selectedStep() === this.name + '.summary') {
                parentForm.triggerSave();
            } else {
                this._super();
            }
        },

        isSelectedStep: function (name) {
            return this.selectedStep() === name;
        },

        showSpecificStepByName: function (name) {
            var index = this.getStepIndexByName(name),
                stepName;
            this.wizard.index = index;
            stepName = this.wizard.move(index);

            this.selectedStep(stepName);
            this.disabled(true);
        }
    });
});
