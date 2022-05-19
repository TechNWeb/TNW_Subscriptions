/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'uiRegistry'
], function ($, registry) {
    return function (originalPriceBundle) {
        $.widget('mage.priceBundle', originalPriceBundle, {

            _onBundleOptionChanged: function onBundleOptionChanged(event) {
                this._super(event)
                $('#subscribe-container').trigger('updateBundlePrice', this.options.optionConfig)
            },

            _updatePriceBox: function () {
                var optionConfig = this.options.optionConfig
                this._super()
                registry.async('recurringAddToCart')(function () {
                    $('#subscribe-container').trigger('updateBundlePrice', optionConfig)
                })
                return this
            }
        })
    }
})
