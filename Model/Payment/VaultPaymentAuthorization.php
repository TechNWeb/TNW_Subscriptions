<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment;

use TNW\Subscriptions\Observer\QuoteSubmitSuccess\CreateProfile;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Command\CommandException;

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
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * VaultPaymentAuthorization constructor.
     * @param CreateProfile $createProfileObserver
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $paymentProcessors
     */
    public function __construct(
        CreateProfile $createProfileObserver,
        \Psr\Log\LoggerInterface $logger,
        $paymentProcessors = []
    ) {
        $this->createProfileObserver = $createProfileObserver;
        $this->paymentProcessors = $paymentProcessors;
        $this->logger = $logger;
    }

    /**
     * @param $paymentData
     * @param $quote
     * @throws CommandException
     */
    public function processPreAuthForTrial($paymentData, $quote)
    {
        if (isset($this->paymentProcessors[$paymentData['method']])) {
            $transferFactory = $this->paymentProcessors[$paymentData['method']]['factory'];
            $dataBuilder = $this->paymentProcessors[$paymentData['method']]['dataBuilder'];
            $client = $this->paymentProcessors[$paymentData['method']]['authClient'];
            $cancelClient = $this->paymentProcessors[$paymentData['method']]['cancelClient'];
            $validator = $this->paymentProcessors[$paymentData['method']]['validator'];
            $voidValidator = $this->paymentProcessors[$paymentData['method']]['voidValidator'];
            $paymentTransactionData = $dataBuilder->build($quote, $paymentData);
            $transferO = $transferFactory->create($paymentTransactionData);

            $response = $client->placeRequest($transferO);

            $result = $validator->validate(
                array_merge($paymentData, ['response' => $response])
            );
            if (!$result->isValid()) {
                $this->processErrors($result);
            }

            $paymentTokenData = $this->paymentProcessors[$paymentData['method']]['vaultTokenExtractor']
                ->getPaymentTokenWithTransactionId($response, $quote);

            $cancelRequest =  [
                'transaction_id' => $paymentTokenData['transaction_id'],
            ];
            if (isset($paymentTransactionData['store_id'])) {
                $cancelRequest['store_id'] = $paymentTransactionData['store_id'];
            }
            if ($this->paymentProcessors[$paymentData['method']]['extendedVoid']) {
                $cancelRequest['paymentTransactionData'] = $paymentTransactionData;
            }
            $transferCancelObject = $transferFactory->create($cancelRequest);
            $responseCancel = $cancelClient->placeRequest($transferCancelObject);

            $voidResult = $voidValidator->validate(
                array_merge($paymentData, ['response' => $responseCancel])
            );
            if (!$voidResult->isValid()) {
                $this->processErrors($voidResult, true);
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
     * Tries to map error messages from validation result and logs processed message.
     * Throws an exception with mapped message or default error.
     *
     * @param ResultInterface $result
     * @param bool $withoutException
     * @return $this
     * @throws CommandException
     */
    private function processErrors(ResultInterface $result, $withoutException = false)
    {
        $messages = [];
        $errorsSource = array_merge($result->getErrorCodes(), $result->getFailsDescription());
        foreach ($errorsSource as $errorCodeOrMessage) {
            $errorCodeOrMessage = (string) $errorCodeOrMessage;
            $this->logger->critical('Payment Error: ' . $errorCodeOrMessage);
        }

        if ($withoutException) {
            return $this;
        }
        throw new CommandException(
            !empty($messages)
                ? __(implode(PHP_EOL, $messages))
                : __('Transaction has been declined. Please try again later.')
        );
    }
}
