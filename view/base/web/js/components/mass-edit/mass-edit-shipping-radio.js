define([
    'Magento_Ui/js/form/element/checkbox-set',
    'underscore'
], function (uiCheckboxSet, _) {
    return uiCheckboxSet.extend({
        defaults: {
            selectedMethodText: false
        },

        initialize: function () {
            var self = this;
            this._super();
            this.value.subscribe(function (value) {
                if (value) {
                    self.selectedMethodText(_.indexBy(self.options(), 'value')[value]['label']);
                } else {
                    self.selectedMethodText('');
                }
            })
        },

        initObservable: function () {
            this._super();
            this.observe('options selectedMethodText');
            return this;
        }
    })
})
