/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(['jquery'], function ($) {
    return function (originalSwatch) {
        $.widget('mage.SwatchRenderer', originalSwatch, {
            options: {
                selectorProductPrice: '[data-role=priceBox]:not(.subscription-price-container)'
            }
        });

        return $.mage.SwatchRenderer;
    }
});
