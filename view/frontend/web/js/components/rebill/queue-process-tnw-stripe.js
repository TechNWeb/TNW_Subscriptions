define([
    'uiElement',
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function (Element, $, $t, alert) {
    return Element.extend({
        defaults: {
            template: 'TNW_Subscriptions/rebill/queue-process',
            stripe: {
                client: null,
                publishableKey: null
            },
            token: null,
            scriptLoaded: null,
            scriptLoading: null,
            sdkUrl: null,
            createUrl: null,
            clientToken: null,
            totalAmount: null
        },

        initialize: function () {
            this._super()
            this.loadScript()
        },

        processRebill: function () {
            var self = this;

            $('body').trigger('processStart')

            $.getJSON(self.createUrl, {
                'data' : JSON.stringify({
                    'public_hash': self.publicHash,
                    'amount' : self.totalAmount,
                    'currency': self.currency
                })
            }).done(function (response) {
                // Disable Payment Token
                if (!response.pi) {
                    $('body').trigger('processStop');
                    return;
                }
                self.authenticateCustomer(response.pi, function (error, response) {
                    if (error) {
                        self.processErrors($t('3D Secure authentication failed.'))
                    } else {
                        $.post(self.processUrl, {
                            formKey: $('input[name=form_key]').val(),
                            paymentMethodNonce: JSON.stringify(response.paymentIntent),
                            token: self.token
                        }).done(function (response) {
                            if (!response.error) {
                                $('.tnw-queue-process-wrapper').remove();
                            } else if (response.error && !response.messages.length) {
                                self.processErrors($t('Something went wrong.'))
                            }
                        }).fail(function () {
                            self.processErrors($t('Something went wrong.'))
                        }).always(function () {
                            $('body').trigger('processStop')
                        })
                    }
                    $('body').trigger('processStop');
                });
            }).fail(function (errorMessage) {
                self.processErrors(errorMessage)
                $('body').trigger('processStop')
            })
        },

        /**
         * Load Stripe js API
         */
        loadScript: function () {
            var self = this;

            if (self.scriptLoaded || self.scriptLoading) {
                return
            }
            self.scriptLoading = true;
            $('body').trigger('processStart');
            require([this.sdkUrl], function () {
                self.stripe.client = window.Stripe(self.stripe.publishableKey);
                $('body').trigger('processStop');
                self.scriptLoaded = true;
            });
        },


        processErrors: function (msg) {
            alert({
                title: $t('Error'),
                content: msg
            })
        },

        /**
         * Authenticate customer
         * @param paymentIntentId
         * @param done
         */
        authenticateCustomer: function (paymentIntentId, done) {
            var self = this
            try {
                this.stripe.client.retrievePaymentIntent.apply(this.stripe.client, [paymentIntentId]).then(function (result) {
                    if (result.error) {
                        return done(result.error, result);
                    }
                    if (result.paymentIntent.status === "requires_action"
                        || result.paymentIntent.status === "requires_source_action") {
                        return self.handleCardAction(paymentIntentId, done);
                    }
                    return done(false, result);
                });
            } catch (e) {
                done(e.message);
            }
        },

        /**
         * Run 3DS check
         * @param paymentIntentId
         * @param done
         */
        handleCardAction: function (paymentIntentId, done) {
            try {
                this.stripe.client.handleCardAction.apply(this.stripe.client, [paymentIntentId]).then(function (result) {
                    if (result.error) {
                        return done(result.error.message, result);
                    }
                    return done(false, result);
                });
            } catch (e) {
                done(e.message);
            }
        },
    })
})
