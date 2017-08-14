/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'jquery',
    'mage/translate'
], function (Abstract, uiRegistry, $j) {
    'use strict';

    return Abstract.extend({
        defaults: {

            defaultNotice: 'The iteration will occur every ...',
            noticeTemplate: 'The iteration will occur every %1 %2',
            plural: 's',

            elementTmpl: 'TNW_Subscriptions/form/element/input',

            imports: {
                'onUnitUpdate': 'index = unit:value'
            }
        },

        /**
         * Invokes initialize method of parent class,
         * contains initialization logic.
         *
         * @returns {exports}
         */
        setInitialValue: function () {
            this._super();
            this.updateValue();

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.updateValue();
        },

        /**
         * Change notice on "Unit" field changed.
         */
        onUnitUpdate: function () {
            this.updateValue();
        },

        /**
         * After element is rendered.
         */
        onElementRender: function () {
            this.updateValue();
        },

        /**
         * Update element notice.
         */
        updateValue: function () {
            var unitField = uiRegistry.get('index = unit');
            var frequencyField = uiRegistry.get('index = frequency');
debugger;
            if (frequencyField && unitField && this.value()) {

                if (frequencyField.value() != 1) {
                    unitField.value += $j.mage.__(this.plural);
                }
            }
        }
    });
});
