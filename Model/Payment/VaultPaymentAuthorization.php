<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment;

use TNW\Subscriptions\Observer\QuoteSubmitSuccess\CreateProfile;

/**
 * Class VaultPaymentAuthorization
 * @package TNW\Subscriptions\Model\Payment
 */
class VaultPaymentAuthorization
{
    /**
     * @var CreateProfile
     */
    private $createProfileObserver;

    /**
     * @var array
     */
    private $paymentProcessors;

    /**
     * VaultPaymentAuthorization constructor.
     * @param CreateProfile $createProfileObserver
     * @param array $paymentProcessors
     */
    public function __construct(
        CreateProfile $createProfileObserver,
        $paymentProcessors = []
    ) {
        $this->createProfileObserver = $createProfileObserver;
        $this->paymentProcessors = $paymentProcessors;
    }

    /**
     * @param $paymentData
     * @param $quote
     */
    public function processPreAuthForTrial($paymentData, $quote)
    {
        if (isset($this->paymentProcessors[$paymentData['method']])) {
            $transferFactory = $this->paymentProcessors[$paymentData['method']]['factory'];
            $dataBuilder = $this->paymentProcessors[$paymentData['method']]['dataBuilder'];
            $client = $this->paymentProcessors[$paymentData['method']]['authClient'];
            $cancelClient = $this->paymentProcessors[$paymentData['method']]['cancelClient'];
            $paymentTransactionData = $dataBuilder->build($quote, $paymentData);
            $transferO = $transferFactory->create($paymentTransactionData);
            $response = $client->placeRequest($transferO);

            //TODO: handle success and failure with exception throw
            try {
                $paymentTokenData = $this->paymentProcessors[$paymentData['method']]['vaultTokenExtractor']
                    ->getPaymentTokenWithTransactionId($response);
            } catch (\Exception $e) {
                //TODO: populate  $paymentTokenData['transaction_id'] with transaction id from above to void the amount
                //TODO: add a flag to throw exception afterwards
            } finally {
                $transferCancelObject = $transferFactory->create(
                    [
                        'transaction_id' => $paymentTokenData['transaction_id'],
                        'store_id' => $paymentTransactionData['store_id']
                    ]
                );
                $responseCancel = $cancelClient->placeRequest($transferCancelObject);
            }

            $trialPaymentData = [
                'payment_data' => $paymentData,
                'payment_token' => $paymentTokenData['payment_token']
            ];

            $this->createProfileObserver->setTrialPaymentData($trialPaymentData);
        } elseif ($paymentData['method'] == 'checkmo') {
            $this->createProfileObserver->setTrialPaymentData($paymentData);
        }
    }

    /**
     * @param $response
     * @return bool
     */
    private function validateResponseResult($response)
    {
        //TODO: implement exception throw on failed transactions
        return true;
    }
}
