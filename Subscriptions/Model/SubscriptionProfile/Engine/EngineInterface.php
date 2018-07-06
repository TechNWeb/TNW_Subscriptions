<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

interface EngineInterface
{
    const PAYMENT_DATA_KEY = 'profile_edit_payment_data';

    /**
     * Sets profile to engine model.
     *
     * @param SubscriptionProfileInterface $profile
     * @return $this
     */
    public function setProfile(SubscriptionProfileInterface $profile);

    /**
     * Gets profile from engine model.
     *
     * @return SubscriptionProfileInterface
     */
    public function getProfile();

    /**
     * Processes profile.
     *
     * @param Quote $quote
     * @return OrderInterface
     */
    public function processProfile(Quote $quote);

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

    /**
     * Processes profile by request data
     *
     * @param $requestData
     * @return $this
     */
    public function processProfileByRequestData($requestData);
}
