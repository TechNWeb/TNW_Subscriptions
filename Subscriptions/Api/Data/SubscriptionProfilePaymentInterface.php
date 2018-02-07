<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfilePaymentInterface
{
    const SUBSCRIPTIONS_PROFILE_PAYMENT_TABLE = 'tnw_subscriptions_subscription_profile_payment';

    /**
     * Constants for field names
     */
    const PAYMENT_ID = 'payment_id';
    const PROFILE_ID = 'subscription_profile_id';
    const ENGINE_CODE = 'engine_code';
    const TOKEN_HASH = 'token_hash';
    const PAYMENT_ADDITIONAL_INFO = 'payment_additional_info';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Gets id.
     *
     * @return string|null
     */
    public function getId();

    /**
     * Sets id.
     *
     * @param $id
     * @return $this
     */
    public function setId($id);

    /**
     * Gets profile id.
     *
     * @return string|null
     */
    public function getProfileId();

    /**
     * Sets profile id.
     *
     * @param string|int $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * Gets engine.
     *
     * @return string|null
     */
    public function getEngineCode();

    /**
     * Sets engine code.
     *
     * @param string $engineCode
     * @return $this
     */
    public function setEngineCode($engineCode);

    /**
     * Gets payment token hash.
     *
     * @return string|null
     */
    public function getTokenHash();

    /**
     * Sets payment token hash.
     *
     * @param string $tokenHash
     * @return $this
     */
    public function setTokenHash($tokenHash);

    /**
     * Gets payment additional info.
     *
     * @return string|null
     */
    public function getPaymentAdditionalInfo();

    /**
     * Sets payment additional info.
     *
     * @param  string $info
     * @return $this
     */
    public function setPaymentAdditionalInfo($info);

    /**
     * Declare subscription profile model instance
     *
     * @param SubscriptionProfileInterface $quote
     * @return $this
     */
    public function setSubscriptionProfile(SubscriptionProfileInterface $profile);

    /**
     * Retrieve subscription profile model instance
     *
     * @return SubscriptionProfileInterface
     */
    public function getSubscriptionProfile();
}
