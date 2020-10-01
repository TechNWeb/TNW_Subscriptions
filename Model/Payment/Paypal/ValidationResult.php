<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

/**
 * Class ValidationResult - used for validation result build for paypal
 */
class ValidationResult
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @param $errorCode
     * @param $errorMessage
     */
    public function setError($errorCode, $errorMessage)
    {
        $this->errors[$errorCode] = $errorMessage;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return count($this->errors) ? false : true;
    }

    /**
     * @return array
     */
    public function getErrorCodes()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getFailsDescription()
    {
        return $this->errors;
    }
}
