/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'jquery',
    'underscore',
    'uiRegistry',
    'mage/translate',
    'Magento_Ui/js/form/form',
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
    'TNW_Subscriptions/js/checkout/action/create-shipping-address',
    'TNW_Subscriptions/js/checkout/data',
    'TNW_Subscriptions/js/checkout/model/data-resolver',
    'TNW_Subscriptions/js/checkout/model/quote',
    'TNW_Subscriptions/js/checkout/model/shipping/service',
    'TNW_Subscriptions/js/checkout/model/shipping/address/form-popup-state',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/validation/validator',
    'TNW_Subscriptions/js/checkout/action/select-shipping-address',
    'TNW_Subscriptions/js/checkout/action/select-shipping-method',
    'TNW_Subscriptions/js/checkout/model/shipping/save/processor',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/service'
], function (
    ko,
    $,
    _,
    registry,
    $t,
    Component,
    modal,
    customer,
    addressList,
    createShippingAddress,
    data,
    dataResolver,
    quote,
    shippingService,
    formPopUpState,
    rateValidator,
    selectShippingAddress,
    selectShippingMethod,
    shippingSaveProcessor
) {
    'use strict';

    var popUp = null;

    return Component.extend({
        defaults: {
            shippingFormTemplate: 'TNW_Subscriptions/checkout/shipping/form',
            shippingMethodListTemplate: 'TNW_Subscriptions/checkout/shipping/method/list',
            shippingMethodItemTemplate: 'TNW_Subscriptions/checkout/shipping/method/item'
        },
        visible: ko.observable(!quote.isVirtual()),
        isCustomerLoggedIn: customer.isLoggedIn,
        isFormPopUpVisible: formPopUpState.isVisible,
        isFormInline: addressList().length === 0,
        isNewAddressAdded: ko.observable(false),
        saveInAddressBook: 1,
        quoteIsVirtual: quote.isVirtual(),

        /**
         * @return {exports}
         */
        initialize: function () {
            var self = this,
                hasNewAddress,
                fieldsetName = 'checkout.steps.shipping.shippingAddress.shipping-address-fieldset';

            this._super();

            dataResolver.resolveShippingAddress();

            hasNewAddress = addressList.some(function (address) {
                return address.getType() === 'new-customer-address';
            });

            this.isNewAddressAdded(hasNewAddress);

            this.isFormPopUpVisible.subscribe(function (value) {
                if (value) {
                    self.getPopUp().openModal();
                }
            });

            registry.async('checkoutProvider')(function (checkoutProvider) {
                var shippingAddressData = data.getShippingAddressFromData();

                if (shippingAddressData) {
                    checkoutProvider.set(
                        'shippingAddress',
                        $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                    );
                }

                checkoutProvider.on('shippingAddress', function (shippingAddrsData) {
                    data.setShippingAddressFromData(shippingAddrsData);
                });

                rateValidator.initFields(fieldsetName);
            });

            return this;
        },

        /**
         * @return {*}
         */
        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
                buttons = this.popUpForm.options.buttons;
                this.popUpForm.options.buttons = [
                    {
                        text: buttons.save.text ? buttons.save.text : $t('Save Address'),
                        class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                        click: self.saveNewAddress.bind(self)
                    },
                    {
                        text: buttons.cancel.text ? buttons.cancel.text : $t('Cancel'),
                        class: buttons.cancel.class ? buttons.cancel.class : 'action secondary action-hide-popup',

                        /** @inheritdoc */
                        click: this.onClosePopUp.bind(this)
                    }
                ];

                /** @inheritdoc */
                this.popUpForm.options.closed = function () {
                    self.isFormPopUpVisible(false);
                };

                this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                this.popUpForm.options.keyEventHandlers = {
                    escapeKey: this.onClosePopUp.bind(this)
                };

                /** @inheritdoc */
                this.popUpForm.options.opened = function () {
                    // Store temporary address for revert action in case when user click cancel action
                    self.temporaryAddress = $.extend(true, {}, data.getShippingAddressFromData());
                };
                popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
            }

            return popUp;
        },

        /**
         * Revert address and close modal.
         */
        onClosePopUp: function () {
            data.setShippingAddressFromData($.extend(true, {}, this.temporaryAddress));
            this.getPopUp().closeModal();
        },

        /**
         * Show address form popup
         */
        showFormPopUp: function () {
            this.isFormPopUpVisible(true);
        },

        /**
         * Save new shipping address
         */
        saveNewAddress: function () {
            var addressData;

            this.source.set('params.invalid', false);
            this.triggerShippingDataValidateEvent();

            if (!this.source.get('params.invalid')) {
                addressData = this.source.get('shippingAddress');
                // if user clicked the checkbox, its value is true or false. Need to convert.
                addressData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;

                // New address must be selected as a shipping address
                selectShippingAddress(createShippingAddress(addressData));

                // New address must be selected as a shipping address
                this.getPopUp().closeModal();
                this.isNewAddressAdded(true);
            }
        },

        /**
         * Shipping Method View
         */
        rates: shippingService.getShippingRates(),
        isLoading: shippingService.isLoading,
        isSelected: ko.computed(function () {
            return quote.shippingMethod() ?
                quote.shippingMethod()['carrier_code'] + '_' + quote.shippingMethod()['method_code'] :
                null;
        }),

        /**
         * @param {Object} shippingMethod
         * @return {Boolean}
         */
        selectShippingMethod: function (shippingMethod) {
            selectShippingMethod(shippingMethod);

            //if (this.validateShippingInformation()) {
                shippingSaveProcessor.saveShippingInformation();
                data.setSelectedShippingRate(shippingMethod['carrier_code'] + '_' + shippingMethod['method_code']);
                return true;
            //}

            return false;
        },

        /**
         * @return {Boolean}
         */
        validateShippingInformation: function () {
            return true;
        },

        /**
         * Trigger Shipping data Validate Event.
         */
        triggerShippingDataValidateEvent: function () {
            this.source.trigger('shippingAddress.data.validate');

            if (this.source.get('shippingAddress.custom_attributes')) {
                this.source.trigger('shippingAddress.custom_attributes.data.validate');
            }
        }
    });
});
