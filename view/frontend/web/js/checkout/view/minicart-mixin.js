define([], function () {
    return function (originalMinicart) {
        return originalMinicart.extend({

            getGroups: function () {
                return this.getCartParam('quoteGroupData') || [];
            },

            getItemsByGroup: function (group) {
                var cartItems = this.getCartItems();
                return _.compact(group['itemIds'].map(function (itemId) {
                    for (let i in cartItems) {
                        if (parseInt(cartItems[i]['item_id']) === parseInt(itemId)) {
                            return cartItems[i];
                        }
                    }
                }.bind(this)));
            },

            getGroupCaption: function (group) {
                return group.caption ? group.caption : '';
            }
        });
    }
});
