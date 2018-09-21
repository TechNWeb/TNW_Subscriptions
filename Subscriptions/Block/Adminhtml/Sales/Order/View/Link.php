<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\Sales\Order\View;

use Magento\Sales\Block\Adminhtml\Order\View\Info as OrderInfo;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\UrlBuilderInterface as UrlBuilderInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager;

/**
 * Class to show Subscription profile link on order view page
 */
class Link extends \Magento\Backend\Block\Template
{
    /**
     * Class to retrieve subscription url
     *
     * @var UrlBuilderInterface
     */
    private $profileUrlBuilder;

    /**
     * Class Manager
     * 
     * @var Manager
     */
    private $manager;

    /**
     * Order Info block
     *
     * @var OrderInfo
     */
    private $orderInfo;

    /**
     * Profile id linked on order
     *
     * @var int
     */
    private $profileId;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param UrlBuilderInterface $profileUrlBuilder
     * @param OrderInfo $orderInfo
     * @param Manager $manager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        UrlBuilderInterface $profileUrlBuilder,
        OrderInfo $orderInfo,
        Manager $manager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->profileUrlBuilder = $profileUrlBuilder;
        $this->manager = $manager;
        $this->orderInfo = $orderInfo;
    }

    /**
     * Can show profile link
     *
     * @return bool
     */
    public function canShow()
    {
        return (bool)$this->retrieveProfileId();
    }

    /**
     * Get subscription edit URL
     *
     * @return string
     */
    public function getLinkUrl()
    {
        if (!$this->retrieveProfileId()) {
            return '';
        }

        return $this->profileUrlBuilder->getEditUrl($this->retrieveProfileId());
    }

    /**
     * Get subscription link label
     * 
     * @return string
     */
    public function getLinkLabel()
    {
        if (!$this->retrieveProfileId()) {
            return '';
        }
        
        return SubscriptionProfileInterface::LABEL_PREFIX . $this->retrieveProfileId();
    }

    /**
     * Retrieve profile id linked on order
     *
     * @return false|int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function retrieveProfileId()
    {
        if ($this->profileId === null) {
            $orderId = $this->orderInfo->getOrder()->getId();
            if ($orderId) {
                $this->profileId = $this->manager->getSubscriptionProfileIdByOrder($orderId);
            } else {
                $this->profileId = false;
            }
        }

        return $this->profileId;
    }
}
