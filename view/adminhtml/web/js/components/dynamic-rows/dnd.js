/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
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
            $(this.body).trigger(this.name + ':afterSetPosition', [ depElementCtx ]);
        }
    });
});
