<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml;

abstract class ProductSubscriptionProfile extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'TNW_Subscriptions::top_level';
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu('TNW_Subscriptions::tnw_subscriptions_product_subscription_profile')
            ->addBreadcrumb(__('TNW'), __('TNW'))
            ->addBreadcrumb(__('Product Subscription Profile'), __('Product Subscription Profile'));
        return $resultPage;
    }
}
