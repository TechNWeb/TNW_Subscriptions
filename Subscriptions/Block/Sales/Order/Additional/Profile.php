<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Sales\Order\Additional;

use Magento\Framework\View\Element\Template;

class Profile extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation
     */
    private $itemRelationResource;

    public function __construct(
        Template\Context $context,
        \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation $itemRelationResource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->itemRelationResource = $itemRelationResource;
    }

    /**
     * @return bool|string
     */
    public function getInitialFee()
    {
        $item = $this->getOrder()->getItemById($this->getItem()->getItemId());
        if (!$item instanceof \Magento\Sales\Model\Order\Item) {
            return 0;
        }

        $extensionAttributes = $item->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Sales\Api\Data\OrderItemExtensionInterface) {
            return 0;
        }

        $initialFees = $extensionAttributes->getSubsInitialFees();
        if (!$initialFees instanceof \TNW\Subscriptions\Model\Sales\ExtensionAttributes\OrderItem) {
            return 0;
        }

        return $initialFees->getSubsInitialFee();
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
     *
     * @return string
     */
    public function textProfileId($profileId)
    {
        return sprintf('S-%d', $profileId);
    }
}
