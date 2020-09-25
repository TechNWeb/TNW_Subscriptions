<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\Sales\Order\View;

/**
 * Class Profile- adminhtml block for profile view
 */
class Profile extends \Magento\Backend\Block\Template
{
    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation
     */
    private $itemRelationResource;

    /**
     * Profile constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation $itemRelationResource
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation $itemRelationResource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->itemRelationResource = $itemRelationResource;
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
        return sprintf('S-%d', $profileId);
    }
}
