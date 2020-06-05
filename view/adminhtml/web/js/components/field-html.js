define([
    'Magento_Ui/js/form/components/html'
], function (Component) {
    return Component.extend({
        /**
         * Has service
         *
         * @returns {Boolean} false.
         */
        hasService: function () {
            return false;
        },

        /**
         * Has addons
         *
         * @returns {Boolean} false.
         */
        hasAddons: function () {
            return false;
        },
    });
});
