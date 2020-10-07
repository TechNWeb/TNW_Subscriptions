<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Sales\Order\Additional;

use Magento\Framework\View\Element\Template;

/**
 * Class Profile - block to disaply the profile on product additional
 */
class Profile extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation
     */
    private $itemRelationResource;

    /**
     * Profile constructor.
     * @param Template\Context $context
     * @param \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation $itemRelationResource
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation $itemRelationResource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->itemRelationResource = $itemRelationResource;
    }

    /**
     * @return \Magento\Sales\Model\Order\Item
     */
    private function getItem()
    {
        return $this->getParentBlock()->getData('item');
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getItem()->getOrder();
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
     * @param int $profileId
     * @return string
     */
    public function linkProfileId($profileId)
    {
        return $this->_urlBuilder->getUrl('tnw_subscriptions/subscription/edit', ['entity_id' => $profileId]);
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
