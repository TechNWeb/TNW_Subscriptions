define([
    'uiElement',
    'underscore',
    'mage/translate',
    'jquery',
    'mage/calendar'
], function (Element, _, $t, $, calendar) {
    return Element.extend({
        defaults: {
            currentProduct: undefined,
            selectedFrequency: null,
            scheduleDateInputVisible: false,
            superAttributeSelectClass: '.super-attribute-select',
            selectedProductInput: '[name=selected_configurable_option]',
            swatchSelector: '#product-options-wrapper .swatch-attribute',
            purchaseTypeRadio: '[name=addtocart_type]',
            activeInputSelector: 'input[name=subscribe_active]',
            infoPriceContainer: '.product-info-price',
            subsPriceBox: '.price-box.price-subscription_price',
            priceBox: '.price-box.price-final_price',
            tracks: {
                currentProduct: true,
                selectedFrequency: true,
                scheduleDateInputVisible: true
            },
            template: 'TNW_Subscriptions/product/subscribe-component'
        },


        initialize: function () {
            this._super();
            if (
                this.products.type === 'simple' ||
                this.products.type === 'virtual' ||
                this.products.type === 'downloadable'
            ) {
                this.currentProduct = this.products.product;
                this.setDefaultFrequency();
            }
            this.bindEvents();
            this.updatePriceBox();
        },

        bindEvents: function () {
            var self = this;
            $(this.superAttributeSelectClass).on('change', function () {
                var productId = self.getSelectedProductId();
                self.setCurrentProduct(productId);
                self.updatePriceBox();
                self.setupCalendar();
            });
            $(this.purchaseTypeRadio).on('change', function (event) {
                var subsActive = $(event.target).val();
                $(self.activeInputSelector).val(subsActive);
                if (subsActive === '1') {
                    $(self.subsPriceBox).show();
                    $(self.priceBox).hide();
                } else {
                    $(self.subsPriceBox).hide();
                    $(self.priceBox).show();
                }
            });
        },

        getSelectedProductId: function () {
            var productId = $(this.selectedProductInput).val(),
                selectedOptions = {},
                attrId, attrValue;
            if (productId) {
                return productId;
            }
            _.each($(this.swatchSelector), function (swatch, index) {
                attrId = $(swatch).attr('attribute-id');
                attrValue = $(swatch).attr('option-selected');
                if (!attrId || !attrValue) {
                    return false;
                }
                selectedOptions[attrId] = attrValue;
            });
            _.each(this.products.super_attributes, function (mappedOptions, id) {
                if(_.difference(_.toArray(mappedOptions), _.toArray(selectedOptions)).length) {
                    return false;
                }
                productId = id;
            })
            return productId;
        },

        setupCalendar: function () {
            $("#start_at").calendar({
                showsTime: false,
                dateFormat: this.dateFormat,
                showOn: 'both',
                minDate: this.minStartOn,
                altField: "#start_on_alt",
                altFormat: "MM dd"
            }).datepicker('setDate', this.defaultStartOn);
            $('.delivery-schedule-date').html($('#start_on_alt').val());
        },

        setCurrentProduct: function (productId) {
            if (!productId) {
                this.currentProduct = undefined;
                this.selectedFrequency = null;
                return false;
            }
            this.currentProduct = this.products.children[productId];
            this.setDefaultFrequency();
        },

        setDefaultFrequency: function () {
            var defaultOption = _.findWhere(this.currentProduct.frequency_data, {'is_default': '1'});
            if (defaultOption) {
                this.selectedFrequency = defaultOption.value;
            }
        },

        getFrequencyOptions: function () {
            var options = [];

            if (!this.currentProduct || !this.currentProduct.frequency_data) return;
            _.each(this.currentProduct.frequency_data, function (option) {
                if (_.indexBy(this.products.product.frequency_data, 'value')[option.value]) {
                    options.push(option);
                }
            }, this);
            return options;
        },

        getFrequencyLabel: function (option) {
            var label = '';
            if (option && option.label) {
                label = option.is_default === '1' ? option.label + $t(' (most common)') : option.label;
            }
            return label;
        },

        updatePriceBox: function () {
            var priceWidget = $('.product-info-price').data('mageTnwSubscribePrice');
            if (priceWidget) {
                priceWidget._insertPriseBox(this.selectedFrequency, this.getSelectedProductId());
            }
        },

        frequencyChanged: function (self, event) {
            this.selectedFrequency = $(event.target).val();
            this.updatePriceBox();
        },

        isInfinite: function () {
            return  !!parseInt(this.get('currentProduct.recurring_settings.inf_subscriptions'));
        },

        getDefaultUntilCancelled: function () {
            return this.isInfinite() ? this.isInfinite() : this.defaultUntilCancelled;
        },

        getIsVisibleStartOn: function () {
            if (
                this.get('currentProduct.trial_data') &&
                this.get('currentProduct.trial_data.trial_start_date') === '2'
            ) {
                return true;
            }
            return this.get('currentProduct.recurring_settings.start_date') === '2';
        },

        getAllowDisplaySubscribeQty: function () {
            return  !parseInt(this.get('currentProduct.recurring_settings.hide_qty'));
        },

        getIsQtyPreset: function () {
            return  !!parseInt(this.get('currentProduct.recurring_settings.unlock_preset_qty'));
        },

        getDefaultSubscribeQty: function () {
            if (this.getIsQtyPreset()) {
                if (this.selectedFrequency && _.indexBy(this.getFrequencyOptions(), 'value')[this.selectedFrequency]) {
                    return _.indexBy(this.getFrequencyOptions(), 'value')[this.selectedFrequency]['preset_qty'] * 1;
                }
                return _.indexBy(this.getFrequencyOptions(), 'is_default')['1']['preset_qty'] * 1;
            }
            return 1;
        },

        manageStartOn: function () {
            this.scheduleDateInputVisible = !this.scheduleDateInputVisible;
            $('.delivery-schedule-date').html($('#start_on_alt').val());
        }

    });
});
