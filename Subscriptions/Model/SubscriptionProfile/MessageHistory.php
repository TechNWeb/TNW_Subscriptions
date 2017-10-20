<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory as ResourceMessageHistory;

/**
 * Model for Subscription profile change history.
 */
class MessageHistory extends AbstractModel implements SubscriptionProfileMessageHistoryInterface
{
    /**
     * @var UrlInterface
     */
    private $backendUrl;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $frontendUrl;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\UrlInterface $frontendUrl,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->backendUrl = $backendUrl;
        $this->frontendUrl = $frontendUrl;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceMessageHistory::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->setData(self::ENTITY_ID, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_ID);

    }

    /**
     * {@inheritdoc}
     */
    public function setParentId($parentId)
    {
        $this->setData(self::PARENT_ID, $parentId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsVisibleOnFront()
    {
        return $this->getData(self::IS_VISIBLE_ON_FRONT);

    }

    /**
     * {@inheritdoc}
     */
    public function setIsVisibleOnFront($isVisibleOnFront)
    {
        $this->setData(self::IS_VISIBLE_ON_FRONT, $isVisibleOnFront);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);

    }

    /**
     * {@inheritdoc}
     */
    public function setMessage($message)
    {
        $this->setData(self::MESSAGE, $message);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsComment()
    {
        return $this->getData(self::IS_COMMENT);

    }

    /**
     * {@inheritdoc}
     */
    public function setIsComment($isUserMessage)
    {
        $this->setData(self::IS_COMMENT, $isUserMessage);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);

    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($createdAt)
    {
        $this->setData(self::CREATED_AT, $createdAt);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId()
    {
        return $this->getData(self::USER_ID);

    }

    /**
     * {@inheritdoc}
     */
    public function setUserId($userId)
    {
        $this->setData(self::USER_ID, $userId);

        return $this;
    }

    /**
     * @return mixed
     */
    public function formatMessage()
    {
        return preg_replace_callback('/\{productUrl\|(\d+)\}/i', function ($matches) {
            switch ($this->_appState->getAreaCode()) {
                case \Magento\Framework\App\Area::AREA_ADMINHTML:
                    return $this->backendUrl->getUrl('catalog/product/edit', ['id' => $matches[1]]);

                case \Magento\Framework\App\Area::AREA_FRONTEND:
                    return $this->frontendUrl->getUrl('catalog/product/view', ['id' => $matches[1]]);

                default:
                    break;
            }

            return '#';
        }, $this->getMessage());
    }
}
