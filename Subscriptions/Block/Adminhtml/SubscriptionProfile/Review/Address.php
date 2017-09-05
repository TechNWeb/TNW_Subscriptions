<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Review;

use Magento\Backend\Block\Template;
use Magento\Customer\Model\Address\Config;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Block for render shipping and billing addresses on subscription profile review page.
 */
class Address extends Template
{
    /**
     * Help retrieve address by type(Shipping, Billing).
     *
     * @var CreateProfile
     */
    private $create;

    /**
     * Address type holder.
     *
     * @var string
     */
    private $addressType;

    /**
     * Help retrieve address format by code.
     *
     * @var Config
     */
    private $addressConfig;

    /**
     * Address constructor.
     *
     * @param Template\Context $context
     * @param CreateProfile $create
     * @param Config $addressConfig
     * @param string $addressType
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CreateProfile $create,
        Config $addressConfig,
        $addressType = QuoteAddress::ADDRESS_TYPE_SHIPPING,
        array $data = []
    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/address.phtml');
        $this->addressType = $addressType;
        $this->create = $create;
        $this->addressConfig = $addressConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get formatted address depends on address type(Shipping, Billing).
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->addressType === QuoteAddress::ADDRESS_TYPE_SHIPPING
            ? $this->format($this->create->getShippingAddress())
            : $this->format($this->create->getBillingAddress());
    }

    /**
     * Get address in proper string format for render.
     *
     * @param QuoteAddress $address
     * @return string
     */
    private function format(QuoteAddress $address)
    {
        $formatType = $this->addressConfig->getFormatByCode('html');

        return $formatType->getRenderer()->renderArray($address->getData());
    }

    /**
     * @return string
     */
    public function getAddressType()
    {
        return $this->addressType;
    }
}
