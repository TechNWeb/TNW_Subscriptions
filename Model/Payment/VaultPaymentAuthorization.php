<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment;

class VaultPaymentAuthorization
{
    private $createProfileObserver;

    private $paymentProcessors;

    public function __construct(
        \TNW\Subscriptions\Observer\QuoteSubmitSuccess\CreateProfile $createProfileObserver,
        $paymentProcessors = []
    ) {
        $this->paymentProcessors = $paymentProcessors;
    }

    public function processPreAuthForTrial($paymentData, $quote)
    {
        if (isset($this->paymentProcessors[$paymentData['method']])) {
            $transferFactory = $this->paymentProcessors[$paymentData['method']]['factory'];
            $dataBuilder = $this->paymentProcessors[$paymentData['method']]['dataBuilder'];
            $client = $this->paymentProcessors[$paymentData['method']]['authClient'];
            $transferO = $transferFactory->create(
                $dataBuilder->build($quote, $paymentData)
            );
            $response = $client->placeRequest($transferO);
            $this->createProfileObserver->setTrialPaymentData($response);
        }
    }
}
