/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

/**
 * Show Profile subscription link in Account Information section on order view page.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.orderProfileLink', {
        options: {
            table_selector: '.admin__page-section-content .admin__table-secondary.order-account-information-table'
        },

        /**
         * Initialize component.
         * @returns {Element}
         */
        _create: function () {
            this.element.appendTo(this.options.table_selector);
            this.element.show();
        }
    });

    return $.mage.orderProfileLink;
});
