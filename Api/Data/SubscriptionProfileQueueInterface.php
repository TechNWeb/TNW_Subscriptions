<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfileQueueInterface
{
    /**#@+
     * Constants for field names
     */
    const ID = 'id';
    const PROFILE_ORDER_ID = 'profile_order_id';
    const STATUS = 'status';
    const ATTEMPT_COUNT = 'attempt_count';
    const MESSAGE = 'message';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
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
     * Gets profile order id.
     *
     * @return string|null
     */
    public function getProfileOrderId();

    /**
     * Sets profile order id.
     *
     * @param string $profileOrderId
     * @return $this
     */
    public function setProfileOrderId($profileOrderId);

    /**
     * Gets status.
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Sets status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Gets attempt count.
     *
     * @return string|null
     */
    public function getAttemptCount();

    /**
     * Sets attempt count.
     *
     * @param string $count
     * @return $this
     */
    public function setAttemptCount($count);

    /**
     * Gets message.
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Sets message.
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Gets created at date.
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Sets created at date.
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);
    /**
     * Gets updated at date.
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Sets updated at date.
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}
