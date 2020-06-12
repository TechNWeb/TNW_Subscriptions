define([
    'Magento_Ui/js/grid/columns/column',
    'mageUtils'
], function (Column, utils) {
    return Column.extend({

        getField: function(row, fieldName) {
            return utils.nested(row, 'product_name.product.' + fieldName);
        },
    });
})
