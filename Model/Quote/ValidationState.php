<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Quote;

/**
 * Class ValidationState - quote product billing frequency validation state
 */
class ValidationState
{
    /**
     * @var
     */
    private $billingFrequencyValidationState = true;

    /**
     * @param $data
     * @return $this
     */
    public function setProductFrequencyValidated($state)
    {
        $this->billingFrequencyValidationState = $state;
        return $this;
    }

    /**
     * @param bool $remove
     * @return mixed
     */
    public function getProductFrequencyValidationState($remove = true)
    {
        $result = $this->billingFrequencyValidationState;
        if ($remove) {
            $this->billingFrequencyValidationState = true;
        }
        return $result;
    }
}
