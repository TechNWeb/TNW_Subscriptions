define([
    'Magento_Ui/js/grid/massactions',
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'uiRegistry'
], function (MassActions, $, alert, $t, registry) {
    return MassActions.extend({
        addLinkedProducts: function (action, data) {
            var itemsType = data.excludeMode ? 'excluded' : 'selected',
                selections = {},
                billingFrequencyId = registry.get('index = billingfrequency_form_data_source').get('data.id');

            selections[itemsType] = data[itemsType];

            if (!selections[itemsType].length) {
                selections[itemsType] = false;
            }

            _.extend(selections, { frequency_id : billingFrequencyId }, data.params || {});

            $.post(
                {
                    url: action.url,
                    data: selections
                }
            ).done(function (data) {
                registry.get('ns = tnw_billingfrequency_form, index = modal').closeModal()
                $('body').notification('clear').notification('add', {
                    error: data.error,
                    message: data.messages.join('<br>'),

                    /**
                     * Inserts message on page
                     * @param {String} msg
                     */
                    insertMethod: function (msg) {
                        $('#anchor-content > .page-main-actions').after(msg);
                    }
                });
            }).fail(function () {
                alert($t('Something went wrong.'))
            });
        },

        triggerAdd: function () {
            this.applyAction('add')
        }
    })
})
