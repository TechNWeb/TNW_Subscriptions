<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Quote\Model\Quote\Payment;

interface EngineInterface
{
    /**
     * Processes profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @return mixed
     */
    public function processProfile(SubscriptionProfileInterface $profile);

    /**
     * Updates profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @return SubscriptionProfileInterface
     */
    public function updateProfile(SubscriptionProfileInterface $profile);

    /**
     * Returns payment information needed for engine.
     *
     * @param Payment $payment
     * @return array
     */
    public function getProfilePaymentInfo(Payment $payment);

    /**
     * Returns information for payment from profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @return array
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile);

    /**
     * Returns additional information for payment from profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @return array
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile);
}
