/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/grid/massactions',
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'uiRegistry'
], function (MassActions, $, alert, $t, registry) {
    return MassActions.extend({
        deleteLinkedProducts: function (action, data) {
            var itemsType = data.excludeMode ? 'excluded' : 'selected',
                selections = {},
                billingFrequencyId = registry.get('index = billingfrequency_form_data_source').get('data.id'),
                self = this;

            selections[itemsType] = data[itemsType]

            if (!selections[itemsType].length) {
                selections[itemsType] = false
            }

            _.extend(selections, { billing_frequency_id : billingFrequencyId }, data.params || {})

            $.post(
                {
                    url: action.url,
                    data: selections
                }
            ).done(function (data) {
                $('body').notification('clear').notification('add', {
                    error: data.error,
                    message: data.messages.join(' '),

                    /**
                     * Inserts message on page
                     * @param {String} msg
                     */
                    insertMethod: function (msg) {
                        $('#anchor-content > .page-main-actions').after(msg)
                    }
                });
            }).fail(function () {
                alert({
                    content: $t('Something went wrong.')
                })
            }).always(function () {
                self.source.reload()
                self.selections().deselectAll()
            });
        }
    })
})
