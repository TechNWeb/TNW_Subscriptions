<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Framework\View\Element\Template;

/**
 * Base block class for subscription profile summary blocks on frontend Customer Account.
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

    /**
     * Return urls to open edit pages.
     *
     * @param string $tabName
     * @param array $params
     * @return string
     */
    public function getEditUrl($tabName = 'edit', array $params = [])
    {
        $params['entity_id'] = $this->getSubscriptionProfile()->getId();

        return $this->getUrl(
            'tnw_subscriptions/subscription/' . $tabName,
            $params
        );
    }
}
