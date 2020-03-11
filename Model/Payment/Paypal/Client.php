<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

/**
 * Class Client
 * @package TNW\Subscriptions\Model\Payment\Paypal
 */
class Client
{
    /**
     * @var \Magento\Paypal\Model\Payflow\Service\Gateway
     */
    private $gateway;

    /**
     * Client constructor.
     * @param \Magento\Paypal\Model\Payflow\Service\Gateway $gateway
     */
    public function __construct(
      \Magento\Paypal\Model\Payflow\Service\Gateway $gateway
    ) {
        $this->gateway = $gateway;
    }

    /**
     * @param $requestData
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function placeRequest($requestData)
    {
        try {
            return $this->gateway->postRequest($requestData['request'], $requestData['config']);
        } catch (\Zend_Http_Client_Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Payment Gateway is unreachable at the moment. Please use another payment option.'),
                $e
            );
        }
    }
}
