<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Shipping;

/**
 * Class Free
 */
class Free
{
    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    private $methodFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateFactory
     */
    private $rateFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Manager constructor.
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $methodFactory
     * @param \Magento\Quote\Model\Quote\Address\RateFactory $rateFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $methodFactory,
        \Magento\Quote\Model\Quote\Address\RateFactory $rateFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->rateFactory = $rateFactory;
        $this->methodFactory = $methodFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $shippingMethod
     * @param $quote
     * @return \Magento\Quote\Model\Quote\Address\Rate
     */
    public function getCarrierRate($quote)
    {
         /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
         $method = $this->methodFactory->create();
         $method->setCarrier('freeshipping');
         $method->setCarrierTitle($this->getConfigData('freeshipping', $quote->getStoreId(), 'title'));
         $method->setMethod('freeshipping');
         $method->setMethodTitle($this->getConfigData('freeshipping', $quote->getStoreId(), 'name'));
         $method->setPrice('0.00');
         $method->setCost('0.00');
        return $this->rateFactory->create()->importShippingRate($method);
    }

    /**
     * @param $methodCode
     * @param $storeId
     * @param $config
     * @return mixed
     */
    protected function getConfigData($methodCode, $storeId, $config)
    {
        return $this->scopeConfig->getValue(
            'carriers/' . $methodCode . '/' . $config,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
