<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Attribute;

/**
 * Subscription Profile attributes grid controller.
 */
class Index extends \TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Attribute
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->createActionPage();
        $resultPage->addContent(
            $resultPage->getLayout()->createBlock(\TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Attribute::class)
        );

        return $resultPage;
    }
}
