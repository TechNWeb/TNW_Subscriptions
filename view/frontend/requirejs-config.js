/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

var config = {
    map: {
        '*': {
            tnwSubscribeContainer: 'TNW_Subscriptions/js/product/subscribe-container',
            tnwSubscribePrice: 'TNW_Subscriptions/js/product/subscribe-price',
            tnwSubConfigurablePrice: 'TNW_Subscriptions/js/product/sub-configurable-price',
            tnwSubscribeShipment: 'TNW_Subscriptions/js/subscription-profile/shipment',
            tnwSubscribeShipmentDetails: 'TNW_Subscriptions/js/subscription-profile/shipment-details',
            tnwSubscribeBilling: 'TNW_Subscriptions/js/subscription-profile/billing',
            tnwSubscribeListButtons: 'TNW_Subscriptions/js/product/list/subscribe-list-buttons',
            configurable: 'TNW_Subscriptions/js/configurable'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/quote' : {
                'TNW_Subscriptions/js/checkout/model/quote-mixin' : true
            },
            'Magento_Checkout/js/view/minicart' : {
                'TNW_Subscriptions/js/checkout/view/minicart-mixin' : true
            },
            'Magento_Checkout/js/view/summary/item/details/subtotal' : {
                'TNW_Subscriptions/js/checkout/view/summary/item/details/subtotal-mixin' : true
            }
        }
    }
};
