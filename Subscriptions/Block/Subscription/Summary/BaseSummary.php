<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Framework\View\Element\Template;

/**
 * Class BaseSummary
 *
 * @method \TNW\Subscriptions\Model\SubscriptionProfile getSubscriptionProfile()
 */
class BaseSummary extends Template
{
    /**
     * Check if it is possible to edit subscription profile.
     * Depends on profile status.
     *
     * @return bool
     */
    public function isShowEditLink()
    {
        return $this->getSubscriptionProfile()->canEditProfile();
    }
}
