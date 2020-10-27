<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

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
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled("Magento_Paypal")) {
            $this->gateway  = $objectManager->get("Magento\Paypal\Model\Payflow\Service\Gateway");
        }
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
