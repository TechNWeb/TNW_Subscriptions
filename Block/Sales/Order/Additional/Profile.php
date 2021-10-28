<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Sales\Order\Additional;

use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Model\ResourceModel\SalesItemRelation;
use TNW\Subscriptions\Model\Config;

/**
 * Class Profile - block to disaply the profile on product additional
 */
class Profile extends \Magento\Framework\View\Element\Template
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
     * @param Template\Context $context
     * @param SalesItemRelation $itemRelationResource
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SalesItemRelation $itemRelationResource,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
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
        return $this->config->getPrefix($this->getItem()->getStore()->getWebsiteId()) . $profileId;
    }
}
