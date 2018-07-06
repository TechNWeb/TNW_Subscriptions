<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

/**
 * Interface for subscription profile status history.
 */
interface SubscriptionProfileStatusHistoryInterface
{
    /**#@+
     * Constants for field names
     */
    const ID = 'id';
    const SUBSCRIPTION_PROFILE_ID = 'subscription_profile_id';
    const STATUS_OLD = 'status_old';
    const STATUS_NEW = 'status_new';
    const USER_ID = 'user_id';
    const CUSTOMER_ID = 'customer_id';
    const CHANGED_AT = 'changed_at';
    const CHANGED_AT_MICRO = 'changed_at_micro';
    /**#@-*/

    /**
     * Gets id.
     *
     * @return string|null
     */
    public function getId();

    /**
     * Sets id.
     *
     * @param string $id
     * @return $this
     */
    public function setId($id);

    /**
     * Gets subscription profile id.
     *
     * @return string|null
     */
    public function getSubscriptionProfileId();

    /**
     * Sets subscription profile id.
     *
     * @param string $profileId
     * @return $this
     */
    public function setSubscriptionProfileId($profileId);

    /**
     * Gets old status.
     *
     * @return string|null
     */
    public function getStatusOld();

    /**
     * Sets old status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatusOld($status);

    /**
     * Gets new status.
     *
     * @return string|null
     */
    public function getStatusNew();

    /**
     * Sets new status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatusNew($status);

    /**
     * Gets admin user id.
     *
     * @return string|null
     */
    public function getUserId();

    /**
     * Sets admin user id.
     *
     * @param string $id
     * @return $this
     */
    public function setUserId($id);

    /**
     * Gets customer id.
     *
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Sets customer id.
     *
     * @param string $id
     * @return $this
     */
    public function setCustomerId($id);

    /**
     * Gets changed at date.
     *
     * @return string|null
     */
    public function getChangedAt();

    /**
     * Sets changed at date.
     *
     * @param string $changedAt
     * @return $this
     */
    public function setChangedAt($changedAt);

    /**
     * Gets changed at microseconds.
     *
     * @return string|null
     */
    public function getChangedAtMicro();

    /**
     * Sets changed at microseconds.
     *
     * @param string $changedAt
     * @return $this
     */
    public function setChangedAtMicro($changedAt);
}
