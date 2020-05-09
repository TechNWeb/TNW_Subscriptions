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
