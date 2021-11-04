<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\Sales\Order\View;

use Magento\Backend\Block\Template\Context;
use TNW\Subscriptions\Model\ResourceModel\SalesItemRelation;
use TNW\Subscriptions\Model\Config;

/**
 * Class Profile- adminhtml block for profile view
 */
class Profile extends \Magento\Backend\Block\Template
{
    /**
     * @var SalesItemRelation
     */
    private $itemRelationResource;

    /**
     * @var Config
     */
    private $config;

    /**
     * Profile constructor.
     *
     * @param Context $context
     * @param SalesItemRelation $itemRelationResource
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        SalesItemRelation $itemRelationResource,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->itemRelationResource = $itemRelationResource;
        $this->config = $config;
    }

    /**
     * Get order item object from parent block
     *
     * @return \Magento\Sales\Model\Order\Item
     */
    public function getItem()
    {
        return $this->getParentBlock()->getData('item');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProfileIds()
    {
        $item = $this->getItem();
        if (!$item instanceof \Magento\Sales\Model\Order\Item) {
            return [];
        }
        return $this->itemRelationResource->profileIdsByOrderItemId($item->getItemId());
    }

    /**
     * @param $profileId
     * @return string
     */
    public function linkProfileId($profileId)
    {
        return $this->_urlBuilder->getUrl('tnw_subscriptions/subscriptionprofile/edit', ['entity_id' => $profileId]);
    }

    /**
     * @param int $profileId
     *
     * @return string
     */
    public function textProfileId($profileId)
    {
        return $this->config->getPrefix($this->getItem()->getStoreId()) . $profileId;
    }
}
