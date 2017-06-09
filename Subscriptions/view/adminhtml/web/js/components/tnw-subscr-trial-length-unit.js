/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/select',
    'jquery',
    'mage/translate',
    'jquery/ui'
], function (Abstract, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            trialLength: 0
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
         *
         * @param importedValue
         */
        changeComment: function(importedValue) {
            //Find out the length of trial period.
            if (typeof(importedValue)!= 'undefined') {
                this.trialLength = importedValue;
            }
            if (this.trialLength == '') {
                this.trialLength = 0;
            }

            var value = this.value();
            if (typeof value == 'undefined') {
                value = 1;
            }
            //Calculate message to show.
            if (this.trialLength != 0) {
                var optionLabel = this.getOption(value).label;
                this.notice = $.mage.__('Trial will end after');
                this.notice += ' ' + this.trialLength + ' ' + optionLabel + '. ';
                this.notice += $.mage.__('Leave blank if product trial is not offered.');
            } else {
                this.notice = $.mage.__('Product trial is not offered.');
            }
            $('#'+this.noticeId).html(this.notice);
        }
    });
});
