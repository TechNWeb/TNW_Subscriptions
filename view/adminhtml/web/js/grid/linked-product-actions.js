/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/grid/columns/actions',
    'underscore',
    'uiRegistry',
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (Actions, _, registry, $, alert, $t) {
    return Actions.extend({

        /**
         * Creates action callback for multiple actions.
         * Magento bug with params as array is solved here
         *
         * @private
         * @param {Object} action - Action's object.
         * @returns {Function} Callback function.
         */
        _getCallbacks: function (action) {
            var callback = action.callback,
                callbacks = [],
                tmpCallback;

            _.each(callback, function (cb) {
                tmpCallback = {
                    action: registry.async(cb.provider),
                    args: _.compact([cb.target, cb.params])
                };
                callbacks.push(tmpCallback);
            });

            return function () {
                _.each(callbacks, function (cb) {
                    cb.action.apply(cb.action, _.flatten(cb.args));
                });
            };
        },

        deleteRecord: function (id) {
            var deleteUrl = this.source().deleteRecordUrl,
                self = this
            $.post(
                deleteUrl,
                {id : id}
            ).done(function (data) {
                if (data.error) {
                    var content =  data.grid_url
                            ? $t('Cannot unlink product from Billing frequency. '
                                + 'Some <a href="%1" target="_blank">subscription profiles</a> use it.')
                            .replace('%1', data.grid_url)
                            : data.messages.join('<br>')
                    alert({
                        title: $t('Cannot unlink product'),
                        content: content
                    })
                } else {
                    self.source().reload()
                }
            }).fail(function () {
                alert($t('Something went wrong...'))
            })
        }
    })
})
