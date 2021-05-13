define([
    'uiElement',
    'underscore',
    'mage/translate',
    'jquery',
    'knockout',
    'mageUtils',
    'mage/calendar',
    'Magento_Catalog/js/price-utils'
], function (Element, _, $t, $, ko, utils, calendar, priceUtils) {
    return Element.extend({
        defaults: {
            activeInputSelector: 'input[name=subscribe_active]',
            subscriptionProducts: [],
            recurringOnlyText: $t('Product is not available for purchase at this time'),
            tracks: {
                subscriptionProducts: true
            },
            template: 'TNW_Subscriptions/product/grouped-subscribe-component'
        },

        initialize: function () {
            var self = this
            this._super()
            this.subscriptionProducts = this.getSubscriptionProducts()
            $('.tnw-addtocart-container').on('beforeOpen', function (e) {
                $(self.activeInputSelector).val(
                    $(e.target).hasClass('addtocart-subscribe-title') ? 1 : 0
                )
            })
            this.manageRecurringOnly()
        },

        manageRecurringOnly: function () {
            var self = this,
                recurringOnlyIds = _.pluck(_.filter(this.products.children, function (child) {
                    return child.recurring_settings.purchase_type === '2'
                }), 'id')
            _.each(recurringOnlyIds, function (id) {
                var priceBox = $('#super-product-table [data-product-id=' + id + ']')

                priceBox.parents('td.item').append('<div>%1</div>'.replace('%1', self.recurringOnlyText))
                    .attr('colspan', 2)
                    .siblings('td.qty').hide().find('.input-text.qty').val(0)
                priceBox.parents('tr').next('.row-tier-price').remove()
                priceBox.remove()
            })
        },

        getSubscriptionProducts: function () {
            var self = this,
                values = _.values(this.products.children);
            _.each(values, function (product) {
                product.selectedFrequency = ko.observable(self.getDefaultFrequency(product))
                product.term = ko.observable('1')
                product.period = ko.observable(2)
                product.startOnDate = ko.observable($.datepicker.formatDate('mm/d/yy', self.getDefaultDate(product)))
                product.startOnDateAlt = ko.observable($.datepicker.formatDate("MM dd", self.getDefaultDate(product)))
            })
            return values;
        },

        getDefaultDate: function (product) {
            var now = new Date(),
                newDate,
                nextMonth = now.getMonth() === 11 ? 0 : now.getMonth()+1,
                fullYear = nextMonth === 0 ? now.getFullYear()+1 : now.getFullYear()
            switch (this.getStartDateType(product)) {
                case '3':
                    //On the last day of the month
                    newDate = new Date(fullYear, nextMonth, 1)
                    newDate.setDate(newDate.getDate() - 1)
                    return newDate
                    break;
                case '4':
                    //First day of the month
                    if (now.getDate() === 1) {
                        return now
                    }
                    return new Date(fullYear, nextMonth, 1)
                    break;
                case '5':
                    //15th
                    if (now.getDate() === 15) {
                        return now
                    }
                    if (now.getDate() < 15) {
                        return new Date(now.getFullYear(), now.getMonth(), 15)
                    }
                    return new Date(fullYear, nextMonth, 15)
                    break;
                default:
                    // Day of purchase or user-defined
                    return now
            }
        },

        getFrequencyOptions: function (product) {
            var options = [];

            if (!product.frequency_data) {
                return false
            }
            _.each(product.frequency_data, function (option) {
                if (_.indexBy(product.frequency_data, 'value')[option.value]) {
                    options.push(option);
                }
            }, this);
            return options.length ? options : false;
        },

        getFieldName: function (product, name) {
            return 'subs_group[' + product.id + '][' + name + ']';
        },

        frequencyChanged: function (product, event) {
            product.selectedFrequency($(event.target).val());
        },

        getDefaultFrequency: function (product) {
            var fId = false
            _.each(product.frequency_data, function (frequency) {
                if (frequency['is_default'] === '1') {
                    fId = frequency.value
                }
            }, this)
            return fId
        },

        getSubscriptionPrice: function (product) {
            return product.subscription_price[product.selectedFrequency()]
        },

        getQtyValidators: function (product) {
            product.qtyValidators['validate-grouped-qty'] = '#subscribe-container'
            return JSON.stringify(_.omit(product.qtyValidators, 'validate-item-quantity'))
        },

        getDefaultSubscribeQty: function (product) {
            if (this.getIsQtyPreset(product)) {
                return _.indexBy(product.frequency_data, 'value')[product.selectedFrequency()]['preset_qty']
            }
            return product.qty;
        },

        getIsQtyPreset: function (product) {
            return utils.nested(product, 'recurring_settings.unlock_preset_qty') === '1'
        },

        isInfinite: function (product) {
            return utils.nested(product, 'recurring_settings.inf_subscriptions') === '1'
        },

        getTermLabel: function (product) {
            if (product.term() === '1') {
                return $t('until canceled')
            }
            return $t('%1 shipment(s)').replace('%1', product.period())
        },

        onDateSelect: function (startOnDate, datepickerWidet) {
            this.startOnDate(startOnDate)
            this.startOnDateAlt($.datepicker.formatDate("MM dd", $(datepickerWidet.input[0]).datepicker('getDate')))
            $(datepickerWidet.input[0]).dropdownDialog('close')
        },

        isEditableStartOn: function (product) {
            return this.getStartDateType(product) === '2'
        },

        getStartDateType: function (product) {
            return utils.nested(product, 'recurring_settings.start_date')
        },

        getPurchaseType: function (product) {
            return utils.nested(product, 'recurring_settings.purchase_type')
        },

        canSubscribe: function (product) {
            return this.getPurchaseType(product) ? this.getPurchaseType(product) !== '1' : false;
        }
    });
});
