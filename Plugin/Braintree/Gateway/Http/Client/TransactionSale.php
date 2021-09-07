<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Http\Client;

use TNW\Subscriptions\Model\Payment\RequestState;

/**
 * Class TransactionSale - plugin to extends the error transaction functionality
 */
class TransactionSale
{
    /**
     * @var RequestState
     */
    private $requestState;

    /**
     * TransactionSale constructor.
     * @param RequestState $requestState
     */
    public function __construct(
        RequestState $requestState
    ) {
        $this->requestState = $requestState;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterPlaceRequest($subject, $result)
    {
        $this->requestState->setCurrentResponse($result);
        return $result;
    }
}
