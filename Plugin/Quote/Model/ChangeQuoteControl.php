<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model;

/**
 * Class ChangeQuoteControl
 * @package TNW\Subscriptions\Plugin\Quote\Model
 */
class ChangeQuoteControl
{
    /**
     * @var null
     */
    private $customer = null;

    /**
     * @param $subject
     * @param callable $proceed
     * @param $quote
     * @return bool
     */
    public function aroundIsAllowed($subject, callable $proceed, $quote)
    {
        $result = $proceed($quote);
        if (
            !$result
            && $this->customer
            && $quote->getCustomerId() == $this->customer->getId()
        ) {
            $result = true;
        }
        return $result;
    }

    /**
     * @param $customer
     * @return $this
     */
    public function setNewCustomer($customer)
    {
        $this->customer = $customer;
        return $this;
    }
}
