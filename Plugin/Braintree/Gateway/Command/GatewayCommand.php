<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Command;

use TNW\Subscriptions\Model\Payment\RequestState;
use Magento\Payment\Gateway\Command\CommandException;
use Braintree\Result\Error;
use Braintree\Transaction;

/**
 * Class GatewayCommand - populate  the command exception code from payment provider
 */
class GatewayCommand
{
    /**
     * @var RequestState
     */
    private $requestState;

    /**
     * GatewayCommand constructor.
     * @param RequestState $requestState
     */
    public function __construct(
        RequestState $requestState
    ) {
        $this->requestState = $requestState;
    }

    /**
     * @param $subject
     * @param callable $callback
     * @param array $commandSubject
     * @return mixed
     * @throws CommandException
     */
    public function aroundExecute(
        $subject,
        callable $callback,
        array $commandSubject
    ) {
        try {
            $result = $callback($commandSubject);
        } catch (CommandException $e) {
            $currentCommandResponse = $this->requestState->getCurrentResponse();
            if ($currentCommandResponse) {
                $currentCommandResponse = reset($currentCommandResponse);
                if ($currentCommandResponse instanceof Error) {
                    /** @var Transaction $transaction */
                    $transaction = $currentCommandResponse->__get('transaction');
                    if (!$transaction) {
                        //Could not use the cascade call due to object types returned
                        $errors = $currentCommandResponse->__get('errors');
                        $errors = $errors->__get('errors');
                        $errors = $errors->__get('nested');
                        $errors = $errors['transaction'];
                        $errors = $errors->__get('errors')[0];
                        $errorCode = $errors->__get('code');
                    } else {
                        $errorCode = $transaction->__get('processorResponseCode');
                    }
                }
            }
            if (isset($errorCode)) {
                throw new CommandException(__($e->getMessage()), null, $errorCode);
            }
            throw $e;
        }
        return $result;
    }
}
