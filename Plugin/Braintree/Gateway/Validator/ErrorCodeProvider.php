<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Validator;

use Braintree\Result\Error;

/**
 * Class ErrorCodeProvider - plugin
 */
class ErrorCodeProvider
{
    /**
     * @param $subject
     * @param $proceed
     * @param $response
     * @return array
     */
    public function aroundGetErrorCodes($subject, $proceed, $response)
    {
        $result = [];
        if ($response instanceof Error
            && isset($response->transaction)
            && !isset($response->transaction->status)
        ) {
            $collection = $response->errors;
            foreach ($collection->deepAll() as $error) {
                $result[] = $error->code;
            }
        } else {
            $result = $proceed($response);
        }
        return $result;
    }
}
