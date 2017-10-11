<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Address\Config as AddressConfig;

/**
 * Class Address
 * @package TNW\Subscriptions\Block\Subscription\Summary
 *
 * @method \TNW\Subscriptions\Model\SubscriptionProfile getSubscriptionProfile()
 */
class Address extends Template
{

    /**
     * @var AddressConfig
     */
    private $addressConfig;

    /**
     * @var string
     */
    private $addressType;

    public function __construct(
        Template\Context $context,
        AddressConfig $addressConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->addressConfig = $addressConfig;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setAddressType($type)
    {
        $this->addressType = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddressType()
    {
        return $this->addressType;
    }

    /**
     * Get address in proper string format for render.
     *
     * @return string
     */
    public function getAddressHtml()
    {
        $addressData = $this->addressType === \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING
            ? $this->getSubscriptionProfile()->getShippingAddress()->getData()
            : $this->getSubscriptionProfile()->getBillingAddress()->getData();

        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($addressData);
    }

    /**
     * @return string
     */
    public function getAddressTitle()
    {
        return $this->addressType === \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING
            ? __('Shipping Information') : __('Billing Information');
    }

    /**
     * @return string
     */
    public function getEditUrl()
    {
        return $this->addressType === \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING
            ? $this->getUrl('tnw_subscriptions/subscription/shipment', ['entity_id' => $this->getSubscriptionProfile()->getId()])
            : $this->getUrl('tnw_subscriptions/subscription/billing', ['entity_id' => $this->getSubscriptionProfile()->getId()]);
    }
}