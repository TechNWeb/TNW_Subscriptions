define([
    'mageUtils'
], function (utils) {
    return function (originalSubtotal) {
        return originalSubtotal.extend({
            getItemSubscriptionPrice: function (item) {
                return utils.nested(item, 'extension_attributes.tnw_subscription_price');
            }
        });
    }
});
