<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

/**
 * Class Address
 */
class Address extends BaseSummary
{
    /**
     * @var AddressConfig
     */
    private $addressConfig;

    /**
     * @var string
     */
    protected $addressType;

    /**
     * Address constructor.
     * @param Template\Context $context
     * @param AddressConfig $addressConfig
     * @param array $data
     */
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
        if (!$this->getSubscriptionProfile()) {
            return '';
        }
        $addressData = $this->addressType === QuoteAddress::ADDRESS_TYPE_SHIPPING
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
        return $this->addressType === QuoteAddress::ADDRESS_TYPE_SHIPPING
            ? __('Shipping Information') : __('Billing Information');
    }

    /**
     * @inheritdoc
     */
    public function getEditUrl($tabName = 'shipment', array $params = [])
    {
        $tabName = ($this->addressType === QuoteAddress::ADDRESS_TYPE_BILLING) ? 'billing' : $tabName;

        $params['shipping_address'] = 1;

        return parent::getEditUrl($tabName, $params);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getSubscriptionProfile()
                && $this->addressType === QuoteAddress::ADDRESS_TYPE_SHIPPING
                && $this->getSubscriptionProfile()->getIsVirtual()
        ) {
            return;
        }

        return parent::_toHtml();
    }
}
