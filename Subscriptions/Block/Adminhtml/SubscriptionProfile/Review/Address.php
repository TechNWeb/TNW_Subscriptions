<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Review;

use Magento\Backend\Block\Template;
use Magento\Customer\Model\Address\Config;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

/**
 * Block for render shipping and billing addresses on subscription profile review page.
 */
class Address extends Template
{
    /* billing addresss type.*/
    const BILLING = 'billing';

    /* shipping address type.*/
    const SHIPPING = 'shipping';

    /**
     * Help retrieve address by type(Shipping, Billing).
     *
     * @var Create
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
     * @param Create $create
     * @param string $addressType
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Create $create,
        Config $addressConfig,
        $addressType = self::BILLING,
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
        switch ($this->addressType) {
            case self::SHIPPING:
                $address = $this->create->getShippingAddress();
                break;
            case self::BILLING:
            default:
                $address = $this->create->getBillingAddress();
                break;
        }

        return $this->format($address);
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
