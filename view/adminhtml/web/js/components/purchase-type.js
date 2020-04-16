define([
    'Magento_Ui/js/form/element/select',
    'mage/translate'
], function (Select, $t) {
    return Select.extend({

        defaults: {
            frequencyRecords: null,
            tracks: {
                frequencyRecords: true
            }
        },

        validate: function () {
            if (this.value() !== '1' && !this.frequencyRecords.length) {
                var message = $t('Please add billing frequencies by clicking \'manage\' button!');
                this.error(message);
                this.error.valueHasMutated();
                this.bubble('error', message);
                if (this.source) {
                    this.source.set('params.invalid', true);
                }
                return {
                    valid: false,
                    target: this
                };
            } else {
                return this._super();
            }
        },

        onUpdate: function () {
            if (this.value() === '1') {
                this.error('');
                this.bubble('error', '');
            }
            this.bubble('update', this.hasChanged());
        }
    });
});
