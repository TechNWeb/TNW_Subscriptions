<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Payments;

use Magento\Backend\Block\Template;
use TNW\Stripe\Model\Ui\ConfigProvider as StripeConfigProvider;

class Stripe extends Template
{
    private $_configProvider;
    /**
     * Stripe constructor.
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        StripeConfigProvider $configProvider,
        array $data = []
    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/payments/stripe.phtml');
        parent::__construct($context, $data);
        $this->_configProvider = $configProvider;
    }
    public function useCcv()
    {
        return $this->gatewayConfig->isCcvEnabled();
    }

    private function getConfig()
    {
        return $this->_configProvider->getConfig();
    }
    public function getPublishableKey()
    {
        $config = $this->getConfig();

        return $config['payment'][StripeConfigProvider::CODE]['publishableKey'] ?? '';
    }
}
