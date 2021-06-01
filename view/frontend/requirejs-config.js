/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

var config = {
    map: {
        '*': {
            tnwSubscribePrice: 'TNW_Subscriptions/js/product/subscribe-price',
            tnwSubscribeShipment: 'TNW_Subscriptions/js/subscription-profile/shipment',
            tnwSubscribeShipmentDetails: 'TNW_Subscriptions/js/subscription-profile/shipment-details',
            tnwSubscribeBilling: 'TNW_Subscriptions/js/subscription-profile/billing',
            tnwSubscribeListButtons: 'TNW_Subscriptions/js/product/list/subscribe-list-buttons',
            calendar: 'mage/calendar'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/quote' : {
                'TNW_Subscriptions/js/checkout/model/quote-mixin' : true
            },
            'Magento_Checkout/js/checkout-data' : {
                'TNW_Subscriptions/js/checkout/checkout-data-mixin' : true
            },
            'Magento_Checkout/js/view/form/element/email' : {
                'TNW_Subscriptions/js/checkout/view/form/element/email-mixin' : true
            },
            'Magento_Checkout/js/view/shipping' : {
                'TNW_Subscriptions/js/checkout/view/shipping-mixin' : true
            },
            'Magento_Checkout/js/view/minicart' : {
                'TNW_Subscriptions/js/checkout/view/minicart-mixin' : true
            },
            'Magento_Checkout/js/view/summary/item/details/subtotal' : {
                'TNW_Subscriptions/js/checkout/view/summary/item/details/subtotal-mixin' : true
            },
            'Magento_Checkout/js/view/summary/cart-items' : {
                'TNW_Subscriptions/js/checkout/view/summary/cart-items-mixin' : true
            },
            'Magento_Swatches/js/swatch-renderer' : {
                'TNW_Subscriptions/js/swatch-renderer-mixin' : true
            },
            'Magento_Catalog/js/catalog-add-to-cart' : {
                'TNW_Subscriptions/js/catalog-add-to-cart-mixin' : true
            },
            'Magento_ConfigurableProduct/js/configurable' : {
                'TNW_Subscriptions/js/configurable-mixin' : true
            },
            'Magento_Catalog/js/product/addtocart-button' : {
                'TNW_Subscriptions/js/product/addtocart-button-mixin' : true
            }
        }
    }
};
