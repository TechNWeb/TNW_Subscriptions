/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
/*jquery:true*/
define([
    'jquery',
    'mage/validation'
], function ($) {
    'use strict';

    $.widget('mage.tnwSubscribeBilling', {
        options: {
            showEdit: 0,
            viewSelector: '#payment-details-view',
            editSelector: '#payment-details-edit',
            editButton: '#payment-details-edit-button'
        },

        /**
         * Initialize widget.
         * @returns void
         */
        _create: function () {
            this._initialize();
            this._bind();
        },

        /**
         * First initialization.
         *
         * @private
         * @returns void
         */
        _initialize: function () {
             var showEdit = this.options.showEdit * 1;

             this.setFormsVisibility(showEdit);

        },

        /**
         * Event binding
         *
         * @private
         * @returns void
         */
        _bind: function () {
            var widget = this,
                editButton = $(this.options.editButton);

            editButton.on('click', $.proxy(function() {
                widget.setFormsVisibility(true);
            }, this));
        },

        /**
         * Display/hide info/edit forms.
         *
         * @param {bool|string} showEdit
         * @returns void
         */
        setFormsVisibility: function(showEdit) {
            this._setElemsVisibility($(this.options.viewSelector), !showEdit);
            this._setElemsVisibility($(this.options.editSelector), showEdit);
        },

        /**
         * Display/hide field.
         *
         * @private
         * @param {jQuery} elem
         * @param {bool|string} visible
         * @returns void
         */
        _setElemsVisibility: function (elem, visible) {
            if (visible) {
                elem.show();
            } else {
                elem.hide();
            }
        }
    });

    return $.mage.tnwSubscribeBilling;
});
