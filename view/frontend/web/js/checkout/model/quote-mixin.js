define([
    'underscore'
], function (_) {
    return function (originalQuote) {
        return _.extend(originalQuote, {

            /**
             * @return {*}
             */
            getItem: function (itemId) {
                var i, item, quoteItemData = window.checkoutConfig.quoteItemData;

                for (i in quoteItemData) {
                    if (!quoteItemData.hasOwnProperty(i)) {
                        continue;
                    }

                    item = quoteItemData[i];
                    if (Number.parseInt(item['item_id']) === Number.parseInt(itemId)) {
                        return item;
                    }
                }

                return null;
            },

            /**
             * @return {*}
             */
            getGroups: function () {
                return window.checkoutConfig.quoteGroupData;
            }
        })
    }
});
