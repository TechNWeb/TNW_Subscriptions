<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Interface for payment form modifier.
 */
interface PaymentModifierInterface extends ModifierInterface
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

    /**
     * Updates form config data.
     *
     * @param array $configData
     * @return array
     */
    public function modifyConfigData(array $configData);
}
