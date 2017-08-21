/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/dynamic-rows/dnd'
], function ($, Dnd) {
    'use strict';

    return Dnd.extend({
        /** @inheritdoc */
        setPosition: function (depElem, depElementCtx, dragData) {
            this._super(depElem, depElementCtx, dragData);
            $(this.body).trigger(this.name + ':setPosition', [ depElementCtx ]);
        }
    });
});
