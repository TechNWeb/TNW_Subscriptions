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
            },

            previousFrequency: false
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
            this.updateUnitValues();

            return this;
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.updateNotice();
            this.updateUnitValues();
        },

        /**
         * Change notice on "Unit" field changed.
         */
        onUnitUpdate: function () {
            this.updateNotice();
            this.updateUnitValues();
        },

        /**
         * After element is rendered.
         */
        onElementRender: function () {
            this.updateNotice();
            this.updateUnitValues();
        },

        /**
         * Update element notice.
         */
        updateNotice: function () {

            var notice = '';
            var unitField = uiRegistry.get('index = unit');
            var frequencyField = uiRegistry.get('index = frequency');

            if (unitField && this.value() && frequencyField) {
                var unitOption = unitField.value();
                var unit = unitField.getOption(unitOption);
                var template = this.noticeTemplate;

                if (frequencyField.value() != 1) {
                    template += this.plural;
                }

                notice = $j.mage.__(template)
                    .replace('%1', this.value())
                    .replace('%2', unit.label.toLowerCase());
            } else {
                notice = $j.mage.__(this.defaultNotice);
            }

            if (notice) {
                if ($j('#' + this.noticeId).length) {
                    $j('#' + this.noticeId).html(notice);
                }
            }
        },

        /**
         * Update unit value which depend of frequency.
         */
        updateUnitValues: function () {
            var unitField = uiRegistry.get('index = unit');
            var frequencyField = uiRegistry.get('index = frequency');

            if (frequencyField && unitField && this.value()) {
                var unitOption = unitField.value();
                var unit = unitField.getOption(unitOption);
                var selector = '[data-index=unit] .admin__control-select [value=' + unit.value + ']';
                var value = 0;
                var options = unitField.indexedOptions;

                if ($j(selector).text() != '') {
                    if (this.previousFrequency == 1 && frequencyField.value() != 1) {
                        //add 's' if frequency is not 1 more.
                        for (value in options) {
                            selector = '[data-index=unit] .admin__control-select [value=' + value + ']';
                            $j(selector).text($j.mage.__($j(selector).text() + this.plural));
                        }
                    } else if (
                        frequencyField.value() == 1
                        && this.previousFrequency != 1
                        && this.previousFrequency !== false
                    ) {
                        //if frequency become 1 so we need remove 's' from the end.
                        for (value in options) {
                            selector = '[data-index=unit] .admin__control-select [value=' + value + ']';
                            var label = $j(selector).text();
                            label = label.substr(0, label.length - this.plural.length);
                            $j(selector).text($j.mage.__(label));
                        }
                    } else if (this.previousFrequency === false && frequencyField.value() != 1) {
                        //check if need add 's' when page is loading.
                        for (value in options) {
                            selector = '[data-index=unit] .admin__control-select [value=' + value + ']';
                            $j(selector).text($j.mage.__($j(selector).text() + this.plural));
                        }
                    }

                    this.previousFrequency = frequencyField.value();
                }
            }
        }
    });
});
