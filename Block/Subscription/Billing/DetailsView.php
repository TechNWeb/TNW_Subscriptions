<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Billing;

use Magento\Framework\View\Element\Template;

use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Block\Subscription\Summary\Payment\Details;

/**
 * Class for subscription profile summary payment details block on frontend Customer Account.
 */
class DetailsView extends Details
{
    /**
     * @inheritdoc
     */
    public function getEditUrl($tabName = 'billing', array $params = [])
    {
        return 'javascript:';
    }

    /**
     * @param $profile
     * @return $this
     */
    public function setSubscriptionProfile($profile)
    {
        $this->subscriptionProfile = $profile;
        return $this;
    }
}
