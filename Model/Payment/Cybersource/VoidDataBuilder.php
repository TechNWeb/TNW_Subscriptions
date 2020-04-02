<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Cybersource;

use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;

class VoidDataBuilder extends \TNW\Subscriptions\Model\Payment\DataBuilder
{
    use \Magento\Payment\Helper\Formatter;

    /**
     * @var mixed
     */
    private $gatewayConfig;

    public function __construct(
        SubscriptionConfig $subscriptionConfig,
        Manager $manager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled("CyberSource_SecureAcceptance")) {
            $this->gatewayConfig = $objectManager->get("CyberSource\SecureAcceptance\Gateway\Config\Config");
        }
        parent::__construct($subscriptionConfig, $manager);
    }

    public function build($data)
    {
        $request = [];
        $paymentData = $data['paymentTransactionData'];
        $request['partnerSolutionID'] = \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID;
        $request['storeId'] = $paymentData['storeId'];
        $request['merchantID'] = $this->gatewayConfig->getValue(
            \CyberSource\SecureAcceptance\Gateway\Config\Config::KEY_MERCHANT_ID,
            $paymentData['storeId']
        );
        $developerId = $this->gatewayConfig->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request['developerId'] = $developerId;
        }
        $request['merchantReferenceCode'] = $paymentData['merchantReferenceCode'];
        $request['purchaseTotals'] = $paymentData['purchaseTotals'];

        $request['ccAuthReversalService'] = [
            'run' => 'true',
            'authRequestID' => $data['transaction_id']
        ];
        return $request;
    }
}
