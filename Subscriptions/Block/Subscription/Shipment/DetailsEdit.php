<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Shipment;

use Magento\Customer\Block\Address\Edit;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Customer address details block
 */
class DetailsEdit extends \Magento\Framework\View\Element\Template
{
    /**
     * Registry model
     *
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * DetailsEdit constructor.
     * @param \Magento\Framework\Registry $registry
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * Retrieve current subscription model instance.
     *
     * @return SubscriptionProfile
     */
    private function getSubscriptionProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
    }
}
