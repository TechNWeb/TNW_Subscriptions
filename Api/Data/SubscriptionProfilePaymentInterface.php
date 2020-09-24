<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface for subscription profile payment.
 */
interface SubscriptionProfilePaymentInterface
{
    /** subscription profile payment table name */
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
    const SENT_MAIL = 'sent_mail';

    /**
     * Gets id.
     *
     * @return string|null
     */
    public function getId();

    /**
     * Sets id.
     *
     * @param int $id
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
     * Gets payment token.
     *
     * @return string|null
     */
    public function getPaymentToken();

    /**
     * Sets payment token hash.
     *
     * @param string $tokenHash
     * @return $this
     */
    public function setTokenHash($tokenHash);

    /**
     * Sets payment token.
     *
     * @param string $token
     * @return $this
     */
    public function setPaymentToken($token);

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
     * Gets payment additional info.
     *
     * @return array|null
     */
    public function getDecodedPaymentAdditionalInfo();

    /**
     * Sets payment additional info.
     *
     * @param  array $info
     * @return $this
     */
    public function setEncodedPaymentAdditionalInfo(array $info);

    /**
     * Declare subscription profile model instance
     *
     * @param SubscriptionProfileInterface $profile
     * @return $this
     */
    public function setSubscriptionProfile(SubscriptionProfileInterface $profile);

    /**
     * Retrieve subscription profile model instance
     *
     * @return SubscriptionProfileInterface
     */
    public function getSubscriptionProfile();

    /**
     * Set sent mail flag
     *
     * @return $this
     */
    public function setSentMail(bool $flag);

    /**
     * Get sent mail flag
     *
     * @return $this
     */
    public function getSentMail();
}
