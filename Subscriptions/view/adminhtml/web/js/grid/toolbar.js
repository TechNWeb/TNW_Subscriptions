/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/grid/toolbar',
    'uiCollection'
], function (toolbar, Collection) {
    'use strict';

    return Collection.extend({
        defaults: {
            template: 'TNW_Subscriptions/grid/toolbar'
        }
    });
});
