/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'jquery',
    'mage/translate',
    'jquery/ui'
], function (Abstract, registry, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            previousTrialLength: false,
            selector : '[data-index = tnw_subscr_trial_length_unit] .admin__control-select [value=%1]',
            plural: 's'
        },

        /**
         * Callback that fires when 'value' property is updated.
         */
        onUpdate: function () {
            this._super();
            this.changeComment();
        },

        /**
         * Fires to change comment.
         */
        changeComment: function() {
            //Find out the type of trial period.
            var unitComponent = registry.get('index=tnw_subscr_trial_length_unit');
            var unitValue = unitComponent.value();
            if (typeof unitValue == 'undefined') {
                unitValue = 1;
            }

            //Find out the length of trial period.
            var trialLength = this.value();
            if (typeof trialLength == 'undefined') {
                trialLength = this.default;
            }

            if (trialLength == '') {
                trialLength = 0;
            }

            //Calculate message to show.
            var option = unitComponent.getOption(unitValue);
            if (trialLength != 0 && (typeof option != 'undefined')) {
                var optionLabel = this.getOptionLabel(unitComponent, unitValue, trialLength);
                this.changeNotice(trialLength, optionLabel);
            } else {
                this.notice = $.mage.__('Product trial is not offered.');
                $('#' + this.noticeId).children().html(this.notice);
            }
        },

        /**
         * Get label for option.
         *
         * @param unitComponent
         * @param unitValue
         * @param trialLength
         * @returns {*|jQuery}
         */
        getOptionLabel: function (unitComponent, unitValue, trialLength) {
            var options = unitComponent.indexedOptions;
            var selector = this.selector.replace('%1', unitComponent.getOption(unitValue).value);
            var value = 0;
            var label = $("[data-index = tnw_subscr_trial_length_unit] .admin__control-select option[value = '" + unitValue +"']").text();

            if (this.previousTrialLength == 1 && trialLength != 1) {
                //add 's' to all options if trialLength is not 1 more.
                for (value in options) {
                    selector = this.selector.replace('%1', value);
                    $(selector).text($(selector).text() + $.mage.__(this.plural));
                }
                label = unitComponent.getOption(unitValue).label + $.mage.__(this.plural);
            } else if (
                trialLength == 1
                && this.previousTrialLength != 1
                && this.previousTrialLength !== false
            ) {
                //if trialLength become 1 so we need remove 's' from the end of all options.
                for (value in options) {
                    selector = this.selector.replace('%1', value);
                    var labelText = $(selector).text();
                    labelText = labelText.substr(0, labelText.length - $.mage.__(this.plural).length);
                    $(selector).text(labelText);
                }
                label = unitComponent.getOption(unitValue).label;
            }

            this.previousTrialLength = trialLength;

            return label;
        },

        /**
         * When element is rendering need to change label and notice depends on trial length.
         */
        onElementRender: function () {
            var unitComponent = registry.get('index=tnw_subscr_trial_length_unit');
            var trialLength = registry.get('index=tnw_subscr_trial_length');

            if (unitComponent && trialLength) {
                var trialLengthValue = trialLength.value();

                var unitValue = unitComponent.value();
                var label = unitComponent.getOption(unitValue).label;
                var options = unitComponent.indexedOptions;

                if (trialLengthValue != 1) {
                    //check if need add to all options 's' when page is loading.
                    for (var value in options) {
                        var selector = this.selector.replace('%1', value);
                        $(selector).text($(selector).text() + $.mage.__(this.plural));
                    }
                    label = unitComponent.getOption(unitValue).label + $.mage.__(this.plural);
                }

                this.changeNotice(trialLengthValue, label);

                this.previousTrialLength = trialLengthValue;
            }

            return this;
        },

        /**
         * Change notice by trial length and option label.
         *
         * @param trialLength
         * @param optionLabel
         */
        changeNotice: function (trialLength, optionLabel) {
            this.notice = $.mage.__('Trial will end after');
            this.notice += ' ' + trialLength + ' ' + optionLabel + '. ';
            this.notice += $.mage.__('Leave blank if product trial is not offered.');

            $('#' + this.noticeId).children().html(this.notice);
        }
    });
});
