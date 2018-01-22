/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/text',
    'mageUtils',
    'uiRegistry'
], function (Text, utils, uiRegistry) {
    'use strict';

    return Text.extend({
        defaults: {
            visible: true,
            label: '',
            error: '',
            uid: utils.uniqueid(),
            disabled: false,
            links: {
                value: '${ $.provider }:${ $.dataScope }'
            },
            url: ''
        },

        initialize: function () {
            this._super();

            var rowIndex = this.dataScope.replace(/[^\d]/g, '');

            var productId = uiRegistry.get('index = linked').source.data.links.linked[rowIndex].id;

            this.url = typeof this.imports.url == "undefined"
                ? '': this.imports.url.replace('linked_id', productId);

            return this;
        }
    });
});
