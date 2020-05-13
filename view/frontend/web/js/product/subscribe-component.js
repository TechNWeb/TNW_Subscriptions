define([
    'uiElement',
    'underscore',
    'mage/translate',
    'jquery',
    'mage/calendar',
    'Magento_Catalog/js/price-utils'
], function (Element, _, $t, $, calendar, utils) {
    return Element.extend({
        defaults: {
            currentProduct: undefined,
            defaultFrequency: false,
            defaultSubscribeQty: 1,
            selectedFrequency: null,
            scheduleDateInputVisible: false,
            recurringQty: null,
            superAttributeSelectClass: '.super-attribute-select',
            selectedProductInput: '[name=selected_configurable_option]',
            swatchWidgetSelector : '.swatch-opt',
            swatchWidgetName : 'mageSwatchRenderer',
            swatchWidgetInitEvent : 'swatch.initialized',
            swatchSelector: '#product-options-wrapper .swatch-attribute',
            purchaseTypeRadio: '[name=addtocart_type]',
            activeInputSelector: 'input[name=subscribe_active]',
            infoPriceContainer: '.product-info-price',
            subsPriceBox: '.price-box.price-subscription_price',
            priceBox: '.price-box.price-final_price',
            altPriceBox: '.onetime-final-price',
            onetimeFields: '#product-addtocart-button, #qty',
            tracks: {
                currentProduct: true,
                selectedFrequency: true,
                recurringQty: true,
                scheduleDateInputVisible: true
            },
            template: 'TNW_Subscriptions/product/subscribe-component'
        },


        initialize: function () {
            var self = this;
            this._super();
            this.getFrequencyLabel = this.getFrequencyLabel.bind(this);

            function initializePreselected() {
                if (self.getSelectedProductId()) {
                    self.setCurrentProduct(self.getSelectedProductId());
                }
                self.bindEvents();
                self.togglePriceBoxes();
            }

            if (
                this.products.type === 'simple' ||
                this.products.type === 'virtual' ||
                this.products.type === 'downloadable'
            ) {
                this.currentProduct = this.products.product;
                this.setDefaultFrequency();
                initializePreselected();
            } else if (this.products.type === 'configurable'){
                if (!$(this.swatchWidgetSelector).length) {
                    initializePreselected();
                    return;
                }
                if ($(this.swatchWidgetSelector).data(this.swatchWidgetName)) {
                    initializePreselected();
                } else {
                    $(this.swatchWidgetSelector).on(this.swatchWidgetInitEvent, initializePreselected.bind(self));
                }
            }
        },

        bindEvents: function () {
            var self = this;
            $(this.superAttributeSelectClass).on('change', function () {
                var productId = self.getSelectedProductId();
                self.setCurrentProduct(productId);
                self.setupCalendar();
                self.togglePriceBoxes();
            });

            $(this.purchaseTypeRadio).on('change', this.togglePriceBoxes.bind(this));
        },

        togglePriceBoxes: function () {
            var selectedPurchaseType = $(this.purchaseTypeRadio + ':checked').val(),
                priceWidget = $('.product-info-price').data('mageTnwSubscribePrice');

            if (priceWidget) {
                priceWidget._insertPriseBox(this.selectedFrequency, this.getSelectedProductId());
            }
            if (selectedPurchaseType === undefined) {
                $(this.subsPriceBox).show();
                $(this.priceBox).hide();
                return;
            }
            $(this.activeInputSelector).val(selectedPurchaseType);
            if (selectedPurchaseType === '1') {
                $(this.subsPriceBox).show();
                $(this.priceBox).hide();
            } else {
                $(this.subsPriceBox).hide();
                $(this.priceBox).show();
            }

            if (this.oneTimePurchaseAllowed()) {
                $(this.altPriceBox).html($(this.priceBox).html());
                $(this.onetimeFields).removeAttr('disabled');
            } else {
                $(this.priceBox).hide();
                $(this.altPriceBox).html($t('There is no one time purchase available for this option'));
                $(this.onetimeFields).attr('disabled', 'disabled');
            }
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
            if (this.defaultFrequency) {
                this.selectedFrequency = this.defaultFrequency;
                return;
            }
            if (defaultOption) {
                this.selectedFrequency = defaultOption.value;
            } else {
                this.selectedFrequency = this.get('currentProduct.frequency_data') ?
                    this.get('currentProduct.frequency_data.0.value') :
                    false;
            }
        },

        getFrequencyOptions: function () {
            var options = [];

            if (!this.currentProduct || !this.currentProduct.frequency_data) return false;
            _.each(this.currentProduct.frequency_data, function (option) {
                if (_.indexBy(this.products.product.frequency_data, 'value')[option.value]) {
                    options.push(option);
                }
            }, this);
            return options.length ? options : false;
        },

        getTrialLabel: function (option) {
            var trialData = this.get('currentProduct.trial_data'),
                trialLabelString = $t(', try for %p %u%p'),
                trialPrice = $t(' FREE');
            if (trialData) {
                trialPrice = trialData.trial_price
                    ? $t(', starting at ') + utils.formatPrice(trialData.trial_price, {}, false)
                    : trialPrice;
                return trialLabelString
                .replace('%p', trialData.trial_length)
                .replace('%u', trialData.trial_label)
                .replace('%p', trialPrice)
            }
            return false;
        },

        getSavingsCalculation: function (option) {
            var type = this.get('currentProduct.recurring_settings.savings_calculation'),
                price = this.get('currentProduct.product_price'),
                recurringPrice = parseFloat(option.price),
                unitType = option.frequency_unit_type,
                frequencyUnit = parseInt(option.frequency_unit),
                qty = option.preset_qty ? option.preset_qty : this.recurringQty,
                saveString = $t(' %p (SAVE ~%s%)'),
                saving,
                priceWithSaving;

            if (type === 2) {
                //formula for service
                if (unitType === '5') {
                    frequencyUnit *= 30;
                }
                saving = ((price * frequencyUnit - recurringPrice) * qty * 100)
                    / (price * frequencyUnit);
            } else if (type === 1) {
                //formula for any retail / physical product with preset qty
                saving = ((price * qty - recurringPrice) * 100)
                    / (price * qty);
            } else {
                //formula for any retail / physical product
                saving = ((price - recurringPrice) * qty * 100) / price;
            }
            saving = parseInt(saving);
            if (saving > 0) {
                priceWithSaving = saveString
                .replace('%p', utils.formatPrice(recurringPrice, {}, false))
                .replace('%s', saving);
            } else {
                priceWithSaving = ' ' + utils.formatPrice(recurringPrice, {}, false);
            }
            return priceWithSaving;
        },

        getFrequencyLabel: function (option) {
            var label = '',
                trialLabel = this.getTrialLabel(option),
                savingsLabel = this.getSavingsCalculation(option);
            if (option && option.label) {
                label = option.is_default === '1' ? option.label + $t(' (most common)') : option.label;
            }
            if (trialLabel) {
                label += trialLabel;
            } else if (savingsLabel) {
                label += savingsLabel;
            }
            return label;
        },

        frequencyChanged: function (self, event) {
            this.selectedFrequency = $(event.target).val();
            this.togglePriceBoxes();
        },

        qtyChanged: function (self, event) {
            this.recurringQty = $(event.target).val();
        },

        isInfinite: function () {
            return  !!parseInt(this.get('currentProduct.recurring_settings.inf_subscriptions'));
        },

        oneTimePurchaseAllowed: function () {
            return this.get('currentProduct.recurring_settings.purchase_type') !== '2';
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
            return this.defaultSubscribeQty;
        },

        getQtyValidators: function () {
            if (this.currentProduct) {
                return JSON.stringify(this.get('currentProduct.qtyValidators'));
            }
            return '';
        },

        manageStartOn: function () {
            this.scheduleDateInputVisible = !this.scheduleDateInputVisible;
            $('.delivery-schedule-date').html($('#start_on_alt').val());
        }

    });
});
