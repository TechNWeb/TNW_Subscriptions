/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/tab_group'
], function (Collection) {
    'use strict';

    return Collection.extend({
        defaults: {
            tabLabel: null
        }
    });
});
