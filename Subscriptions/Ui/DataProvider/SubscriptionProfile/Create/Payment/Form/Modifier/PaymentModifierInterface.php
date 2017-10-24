<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

/**
 * Interface for payment form modifier.
 */
interface PaymentModifierInterface
{
    /**
     * Sets form name.
     *
     * @param string $name
     * @return $this
     */
    public function setPaymentFormName($name);

    /**
     * Gets payment form name.
     *
     * @return string
     */
    public function getPaymentFormName();
}
