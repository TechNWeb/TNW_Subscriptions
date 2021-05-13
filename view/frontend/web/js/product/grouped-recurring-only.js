define([
    'jquery',
    'mageUtils',
    'underscore'
], function ($, utils, _) {
    return function (config) {
        var products = utils.nested(config, 'products.children'),
            recurringOnlyIds = _.pluck(_.filter(products, function (child) {
                return child.recurring_settings.purchase_type === '2'
            }), 'id')

        _.each(recurringOnlyIds, function (id) {
            var priceBox = $('#super-product-table [data-product-id=' + id + ']')

            priceBox.parents('td.item').append('<div>%1</div>'.replace('%1', config.message))
            .attr('colspan', 2)
            .siblings('td.qty').hide().find('.input-text.qty').val(0)
            priceBox.parents('tr').next('.row-tier-price').remove()
            priceBox.remove()
        })
    }
})
