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

        initialize: function () {
            this._super();
            this.changeComment();

            return this;
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
            this.notice = '';
            var value = this.value();
            if (value ==1) {
                this.notice = $.mage.__('Subscription will automatically start when customer completes the checkout.');
            } else if (value ==2) {
                this.notice = $.mage.__('Customer will specify when the subscription for the product should start.');
            } else if (value ==3) {
                this.notice = $.mage.__('Subscription will automatically start on the last day of the current month.');
            }

            $('#'+this.noticeId).html(this.notice);
        }
    });
});
