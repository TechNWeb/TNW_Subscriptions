define([
    'Magento_Ui/js/form/element/abstract'
], function (Element) {
    return Element.extend({
        onTermChange: function (value) {
            this.visible(!parseInt(value));
        }
    })
})
