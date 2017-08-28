<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Dashboard;

use Magento\Backend\Block\Template;

/**
 * todo: implement logic for rendering subscription profile overview information.(SUB-77)
 */
class Overview extends Template
{
    /**
     * @inheritdoc
     */
    public function __construct(Template\Context $context, array $data = [])
    {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/dashboard/overview.phtml');
        parent::__construct($context, $data);
    }
}
