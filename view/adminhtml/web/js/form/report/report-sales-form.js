define([
    'Magento_Ui/js/form/form',
    'uiRegistry',
    'underscore'
], function (UiForm, uiRegistry, _) {
    return UiForm.extend({
        defaults: {
            grid_source_name: ''
        },

        filter: function () {
            this.validate()
            if (!this.source.get('params.invalid')) {
                var gridSource = uiRegistry.get(this.grid_source_name)

                gridSource.set('params', _.extend({}, gridSource.get('params'), this.source.get('data')))
            } else {
                this.focusInvalid()
            }
        }
    })
})
