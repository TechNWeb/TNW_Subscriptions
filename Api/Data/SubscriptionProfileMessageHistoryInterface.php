<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfileMessageHistoryInterface
{
    /**#@+
     * Constants for field names
     */
    const ENTITY_ID = 'entity_id';
    const PARENT_ID = 'parent_id';
    const IS_VISIBLE_ON_FRONT = 'is_visible_on_front';
    const MESSAGE = 'message';
    const IS_COMMENT = 'is_comment';
    const CREATED_AT = 'created_at';
    const USER_ID = 'user_id';
    /**#@-*/

    /**
     * Get id.
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set id.
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get parent id.
     *
     * @return int|null
     */
    public function getParentId();

    /**
     * Set parent id.
     *
     * @param int $parentId
     * @return $this
     */
    public function setParentId($parentId);

    /**
     * Get is_visible_on_front.
     *
     * @return bool|null
     */
    public function getIsVisibleOnFront();

    /**
     * Set is_visible_on_front.
     *
     * @param bool $isVisibleOnFront
     * @return $this
     */
    public function setIsVisibleOnFront($isVisibleOnFront);

    /**
     * Get message.
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Set message.
     *
     * @param string $comment
     * @return $this
     */
    public function setMessage($comment);

    /**
     * Get is_comment.
     *
     * @return bool|null
     */
    public function getIsComment();

    /**
     * Set is_comment.
     *
     * @param bool $isComment
     * @return $this
     */
    public function setIsComment($isComment);

    /**
     * Get created_at.
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at.
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get user_id.
     *
     * @return int|null
     */
    public function getUserId();

    /**
     * Set user_id.
     *
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId);
}
