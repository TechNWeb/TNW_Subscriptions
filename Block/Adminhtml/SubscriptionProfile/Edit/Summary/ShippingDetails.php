<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Summary;

use Magento\Backend\Block\Template;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Class ShippingDetails - adminhtml edit summary block for shipping details info
 */
class ShippingDetails extends Template
{
    /**
     * Subscription profile
     *
     * @var SubscriptionProfileInterface
     */
    private $subscriptionProfile;

    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * ShippingDetails constructor.
     * @param ProfileManager $profileManager
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        ProfileManager $profileManager,
        Template\Context $context,
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        $this->setTemplate('TNW_Subscriptions::subscription_profile/summary/shipping_details.phtml');
        parent::__construct($context, $data);
    }

    /**
     * Return subscription shipping description
     *
     * @return null|string
     */
    public function getShippingDescription()
    {
        return $this->getProfile()->getShippingDescription();
    }

    /**
     * Return subscription profile from registry
     *
     * @return null|SubscriptionProfileInterface
     */
    private function getProfile()
    {
        if (null === $this->subscriptionProfile) {
            /** @var SubscriptionProfile $model */
            $this->subscriptionProfile =
                $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        }
        return $this->subscriptionProfile;
    }
}
