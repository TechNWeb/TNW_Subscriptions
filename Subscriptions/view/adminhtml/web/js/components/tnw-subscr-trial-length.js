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
            if (trialLength == '') {
                trialLength = 0;
            }

            //Calculate message to show.
            var option = unitComponent.getOption(unitValue);
            if (trialLength != 0 && (typeof option != 'undefined')) {
                var optionLabel = unitComponent.getOption(unitValue).label;
                this.notice = $.mage.__('Trial will end after');
                this.notice += ' ' + trialLength + ' ' + optionLabel + '. ';
                this.notice += $.mage.__('Leave blank if product trial is not offered.');
            } else {
                this.notice = $.mage.__('Product trial is not offered.');
            }

            $('#'+this.noticeId).html(this.notice);
        }
    });
});
