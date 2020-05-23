/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(['jquery'], function ($) {
    return function (originalSwatch) {
        $.widget('mage.SwatchRenderer', originalSwatch, {
            options: {
                selectorProductPrice: '[data-role=priceBox]:not(.subscription-price-container)'
            },
            _create: function () {
                this._super();
                this.productForm = this.element.parents(this.options.selectorProductTile).find('form[data-role*="tocart"]');
            }
        });

        return $.mage.SwatchRenderer;
    }
});
