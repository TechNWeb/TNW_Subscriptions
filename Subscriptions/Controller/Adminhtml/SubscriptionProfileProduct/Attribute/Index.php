<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute;

use Magento\Backend\Model\View\Result\Page;
use TNW\Subscriptions\Block\Adminhtml\ProductSubscriptionProfile\Attribute as AttributeBlock;

/**
 * Profile product attributes grid controller.
 */
class Index extends \TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute
{
    /**
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->createActionPage();
        $resultPage->addContent(
            $resultPage->getLayout()->createBlock(AttributeBlock::class)
        );

        return $resultPage;
    }
}
