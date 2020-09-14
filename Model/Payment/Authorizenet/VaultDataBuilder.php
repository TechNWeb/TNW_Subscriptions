<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Authorizenet;

use \Magento\Payment\Gateway\Config\Config;
use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;
use \Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class VaultDataBuilder
 * @package TNW\Subscriptions\Model\Payment\Authorizenet
 */
class VaultDataBuilder extends \TNW\Subscriptions\Model\Payment\DataBuilder
{
    use \Magento\Payment\Helper\Formatter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var
     */
    private $subjectReader;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $tokenManagement;

    public function __construct(
        PaymentTokenManagementInterface $tokenManagement,
        SubscriptionConfig $subscriptionConfig,
        Manager $manager,
        ObjectManager $objectManager,
        Config $config = null
    ) {
        $this->tokenManagement = $tokenManagement;
        $this->manager = $manager;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->config = $config ? : $objectManager->get(Config::class);
        parent::__construct($subscriptionConfig, $manager);
    }

    /**
     * @param $order
     * @param $paymentData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    public function build($order, $paymentData)
    {
        $publicHash = $paymentData['additional_data']['public_hash'];
        $paymentToken = $this->tokenManagement->getByPublicHash($publicHash, $order->getCustomerId());
        list($profileId, $paymentProfileId)
            = explode('/', $paymentToken->getGatewayToken(), 2);
        return [
            'transaction_request' => [
                'profile' => [
                    'customer_profile_id' => $profileId,
                    'payment_profile' => [
                        'payment_profile_id' => $paymentProfileId
                    ]
                ],
                'amount' => $this->formatPrice($this->getAmount($order)),
                'currency_code' => $order->getCurrencyCode(),
                'po_number' => $order->getOrderIncrementId(),
            ],
            'store_id' => $order->getStoreId()
        ];
    }
}
