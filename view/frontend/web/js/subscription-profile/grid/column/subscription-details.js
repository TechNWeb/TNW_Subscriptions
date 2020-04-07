define([
    'jquery',
    'Magento_Ui/js/grid/columns/column',
    'underscore',
    'Magento_Ui/js/modal/confirm',
    'uiRegistry'
], function ($, Column, _, confirm, registry) {
    return Column.extend({

        applyAction: function(component, action) {
            function doPost(action) {
                var self = this;
                $('body').trigger('processStart');
                $.post(action.href, {isAjax: true})
                .done(function () {
                    registry.get(self.provider).set('params.t', Date.now());
                })
                .always(function() {
                    $('body').trigger('processStop');
                });
            }

            if (action.title && action.message) {
                confirm({
                    title: action.title,
                    content: action.message,
                    actions: {
                        confirm: doPost.bind(component, action)
                    }
                });
            } else if (action.type !== 'edit') {
                doPost.bind(component, action)();
            } else {
                window.location.href=action.href;
            }
        },

        getProductName: function (row) {
            if (!row.subscription_product) return false;
            return row.subscription_product.name;
        },

        getProductQty: function (row) {
            if (!row.subscription_product) return false;
            return parseFloat(row.subscription_product.qty);
        },

        getProudctThumbnailSrc: function(row) {
            if (!row.subscription_product) return false;
            return row.subscription_product.img_src;
        },

        getProudctThumbnailAlt: function(row) {
            if (!row.subscription_product) return false;
            return row.subscription_product.img_alt;
        },

        getProductDescription: function (row) {
            if (!row.subscription_product) return false;
            return row.subscription_product.short_description;
        },

        getProductOptions: function (row) {
            if (!row.subscription_product || _.isEmpty(row.subscription_product.configurable_options)) return false;
            return row.subscription_product.configurable_options;
        },

        getIsVirtual: function(row) {
            return row.is_virtual;
        },

        getStatusLabel: function(row) {
            return row.status_label;
        },

        getStatusCssClass: function(row) {
            return 'sub-status_' + row.status;
        },

        getProfileActions: function(row) {
            if (_.isEmpty(row.profile_actions)) return false;
            return row.profile_actions;
        },

        getSubscriptionEditLink: function (row) {
            var actions = _.indexBy(this.getProfileActions(row), 'type');
            return actions.edit.href;
        },

        getSubBillingFrequency: function (row) {
            return row.frequency_label;
        },

        getNextOrderOn: function (row) {
            return row.next_date;
        },

        getProductTerm: function (row) {
            return row.term_label;
        },

        getPaymentAmount: function (row) {
            return row.grand_total;
        },

        getShippingAddress: function (row) {
            return row.shipping_address;
        },

        getShippingMethod: function (row) {
            return row.shipping_method;
        },

        getBillingAddress: function (row) {
            return row.billing_address;
        },

        getPaymentMethodTitle: function (row) {
            return row.payment_method;
        },

        getPaymentDescription: function (row) {
            return row.payment_description;
        },

        getCcType: function (row) {
            if (!row.payment_description) return false;
            return row.payment_description.cc_type;
        },

        getCcNumber: function (row) {
            if (!row.payment_description) return false;
            return row.payment_description.cc_number;
        },

        getCcExp: function (row) {
            if (!row.payment_description) return false;
            return row.payment_description.cc_exp;
        },

        getCurrency: function (row) {
            if (!row.payment_description) return false;
            return row.payment_description.currency;
        }
    })
});
