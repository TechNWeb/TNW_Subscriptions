/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/fieldset',
    'TNW_Subscriptions/js/formatPrice',
    'uiRegistry',
    'underscore',
    'mage/translate'
], function (Collection, formatPrice, uiRegistry, _, $t) {
    'use strict';

    return Collection.extend({
        defaults: {
            notificationMessage: {
                text: null,
                error: null
            },
            wizardSummaryLabel: null,
            frequencyRecords: null,
            indexedFrequencies: null,
            originalPrice: null,
            priceSymbol: null,
            trialUnit: null,
            trialUnitOptions: null,
            trialPrice: null,
            trialStatus: null,
            trialLength: null,
            trialStart: null,
            trialStartOptions: null,
            lockPrice: null,
            offerFlatDiscount: null,
            flatDiscountType: null,
            flatDiscountAmount: null,
            infiniteSubscription: null,
            tracks: {
                label: true,
                frequencyRecords: true,
                trialUnit: true,
                trialPrice: true,
                trialStatus: true,
                trialLength: true,
                trialStart: true,
                lockPrice: true,
                offerFlatDiscount: true,
                flatDiscountType: true,
                flatDiscountAmount: true,
                infiniteSubscription: true
            }
        },

        render: function (wizard) {
            this.wizard = wizard;
            this.label = this.wizardSummaryLabel;
        },

        force: function (wizard) {
            uiRegistry.get(this.parentName).close();
        },

        back: function () {
        },

        setFrequencyRecords: function (frequencyRecords) {
            this.frequencyRecords = _.filter(frequencyRecords, function (elem) {
                return elem && elem['is_delete'] !== '1';
            }, this);
        },

        getRecurringPrice: function (record) {
            return this.priceSymbol + formatPrice.formatPrice(record.price, this.priceFormat);
        },

        getInitialFee: function (record) {
            var fee = parseInt(record.initial_fee) === 0
                ? 'None'
                : this.priceSymbol + formatPrice.formatPrice(record.initial_fee, this.priceFormat);
            return $t('Initial Fee: %').replace('%', fee);
        },

        getFrequencyLabel: function (record) {
            return this.indexedFrequencies[record.billing_frequency_id];
        },

        getOriginalPrice: function () {
            return this.priceSymbol + formatPrice.formatPrice(this.originalPrice, this.priceFormat);
        },

        getIsDefault: function (record) {
            return record.default_billing_frequency === '1' ? 'V' : '';
        },

        getTrialUnit: function () {
            return this.trialUnitOptions[this.trialUnit].label;
        },

        getTrialPrice: function () {
            return this.priceSymbol + formatPrice.formatPrice(this.trialPrice, this.priceFormat);
        },

        getTrialStartLabel: function () {
            return this.trialStartOptions[this.trialStart].label.toLowerCase();
        },

        getTrialDescription: function () {
            var trial = $t('A %1 %2 trial for %3 will be offered and it will start at %4.');
            if (parseInt(this.trialStatus)) {
                return trial.replace('%1', this.trialLength)
                    .replace('%2', this.getTrialUnit())
                    .replace('%3', this.getTrialPrice())
                    .replace('%4', this.getTrialStartLabel());
            }
            return false;
        },

        getDiscountDescription: function () {
            var discount = $t('For recurring purchases a %1 discount is offered%2.');
            if (this.lockPrice === '1' && this.offerFlatDiscount === '1') {
                var discountAmount = this.flatDiscountType === '1' ? this.getDiscountAmount() : this.flatDiscountAmount + '%',
                    trial = parseInt(this.trialStatus) ? $t(' after the trial') : '';

                return discount.replace('%1', discountAmount).replace('%2', trial);
            }
            return false;
        },

        getBillingDescription: function () {
            if (this.infiniteSubscription === '1') {
                return $t('The customer will be billed indefinitely until they choose to cancel.');
            }
            return false;
        },

        getDiscountAmount: function () {
            return this.priceSymbol + formatPrice.formatPrice(this.flatDiscountAmount, this.priceFormat);
        },

        onChangedPurchaseType: function (typeValue) {
            if (typeValue === '1') {
                this.visible(false);
                return false;
            }
            this.visible(true);
        }
    });
});
