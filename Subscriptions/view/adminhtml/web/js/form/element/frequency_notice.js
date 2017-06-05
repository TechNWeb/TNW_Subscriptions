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
            this.updateNotice();

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.updateNotice();
        },

        /**
         * Change notice on "Unit" field changed.
         */
        onUnitUpdate: function () {
            this.updateNotice();
        },

        /**
         * After element is rendered.
         */
        onElementRender: function () {
            this.updateNotice();
        },

        /**
         * Update element notice.
         */
        updateNotice: function () {

            var notice = '';
            var unitField = uiRegistry.get('index = unit');
            if (unitField && this.value()) {
                var unitOption = unitField.value();
                var unit = unitField.getOption(unitOption);

                notice = $j.mage.__(this.noticeTemplate)
                    .replace('%1', (this.value() == 1 ? '' : this.value()))
                    .replace('%2', unit.label.toLowerCase());
            } else {
                notice = $j.mage.__(this.defaultNotice);
            }

            if (notice) {
                if ($j('#' + this.noticeId).length) {
                    $j('#' + this.noticeId).html(notice);
                }
            }
        }
    });
});
