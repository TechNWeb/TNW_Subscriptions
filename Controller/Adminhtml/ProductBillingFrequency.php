<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml;

/**
 * Class ProductBillingFrequency - abstract controller
 */
abstract class ProductBillingFrequency extends \Magento\Backend\App\Action
{
    /**
     * ACL value
     */
    const ADMIN_RESOURCE = 'TNW_Subscriptions::top_level';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    ) {
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
        $resultPage->setActiveMenu('TNW_Subscriptions::tnw_subscriptions_product_billing_frequency')
            ->addBreadcrumb(__('TNW'), __('TNW'))
            ->addBreadcrumb(__('Product Billing Frequency'), __('Product Billing Frequency'));
        return $resultPage;
    }
}
