/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/select',
    'TNW_Subscriptions/js/form/element/visibility/strategy'
], function (Element, strategy) {
    'use strict';

    return Element.extend(strategy);
});
