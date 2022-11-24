/**
 * Copyright © TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'braintreeThreeDSecure',
    'Magento_Checkout/js/model/quote',
    'mage/utils/wrapper',
    'underscore'
], function (
    braintree3dSecure,
    quote,
    wrapper,
    _
) {
    // Override braintree create() function for callback interception.
    braintree3dSecure.create = wrapper.wrap(braintree3dSecure.create, function () {
        var ctx = this,
            args = _.toArray(arguments),
            originalCreate = args[0],
            callback = args[2];

        if (callback) {
            // Wrap braintree 3d secure instance creation callback for overriding instance method
            args[2] = wrapper.wrap(callback, function () {
                var cbCtx = this,
                    cbArgs = _.toArray(arguments),
                    originalCallback = cbArgs[0],
                    threeDSErr = cbArgs[1],
                    threeDSInstance = cbArgs[2];

                if (!threeDSErr && threeDSInstance) {
                    // Wrap verifyCard() method for handling zero amount.
                    threeDSInstance.verifyCard = wrapper.wrap(threeDSInstance.verifyCard, function () {
                        var verifyCardCtx = this,
                            verifyCardArgs = _.toArray(arguments),
                            originalVerifyCard = verifyCardArgs[0],
                            params = verifyCardArgs[1],
                            paymentMethod = quote.paymentMethod() && quote.paymentMethod().method;

                        if (paymentMethod &&
                            (paymentMethod === 'braintree' || /^braintree_cc_vault.*$/.test(paymentMethod))
                            && parseFloat(params.amount) < 0.001
                        ) {
                            params.amount = window.checkoutConfig.staticAuthAmount;
                        }

                        return originalVerifyCard.apply(verifyCardCtx, verifyCardArgs.slice(1));
                    });
                }

                return originalCallback.apply(cbCtx, cbArgs.slice(1));
            });
        }

        return originalCreate.apply(ctx, args.slice(1));
    });

    return function (braintree3ds) {
        return braintree3ds;
    }
});
