<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Payments;

use Magento\Backend\Block\Template;

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
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/payments/stripe.phtml');
        parent::__construct($context, $data);
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->_configProvider = $objectManager->get(\TNW\Stripe\Model\Ui\ConfigProvider::class);
        }
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
