define([
    'Magento_Ui/js/grid/columns/column'
], function (Column) {
    return Column.extend({

        getProductName: function (row) {
            if (!row.subscription_product) return false;
            return row.subscription_product.name;
        },

        getProductQty: function (row) {
            if (!row.subscription_product) return false;
            return parseInt(row.subscription_product.qty);
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
            if (!row.subscription_product || !row.subscription_product.configurable_options.length) return false;
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

        getSubscriptionEditLink: function (row) {
            if (!row.label || !row.label.edit) return false;
            return row.label.edit.href;
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
