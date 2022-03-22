<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model\Quote\Validator\MinimumOrderAmount;

use TNW\Subscriptions\Model\Quote\ValidationState;

/**
 * Class ValidationMessage - modify validation message if the product billing frequency is not relevant.
 */
class ValidationMessage
{
    /**
     * @var ValidationState
     */
    private $validationState;

    /**
     * ValidationMessage constructor.
     * @param ValidationState $validationState
     */
    public function __construct(
        ValidationState $validationState
    ) {
        $this->validationState = $validationState;
    }

    /**
     * @param $subject
     * @param $result
     * @return \Magento\Framework\Phrase
     */
    public function afterGetMessage($subject, $result)
    {
        if (!$this->validationState->getProductFrequencyValidationState(true)) {
            $result = __('Selected product billing frequency is not relevant.');
        }
        return $result;
    }
}
