<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Payments;

use Magento\Backend\Block\Template;

/**
 * Class Stripe - adminhtml block for stripe config
 */
class Stripe extends Template
{
    /**
     * @var mixed
     */
    private $configProvider;

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
            $this->configProvider = $objectManager->get("TNW\Stripe\Model\Ui\ConfigProvider");
        }
    }

    /**
     * @return mixed
     */
    private function getConfig()
    {
        return $this->configProvider->getConfig();
    }

    /**
     * @return mixed|string
     */
    public function getPublishableKey()
    {
        $config = $this->getConfig();

        return $config['payment']['tnw_stripe']['publishableKey'] ?? '';
    }
}
