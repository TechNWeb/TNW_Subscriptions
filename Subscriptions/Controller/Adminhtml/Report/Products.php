<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\Report;

class Products extends \Magento\Backend\App\Action
{
    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TNW_Subscriptions::report_subscribe_products');
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

        $this->_setActiveMenu('Magento_Reports::report_customers_accounts')
            ->_addBreadcrumb(__('Reports'), __('Reports'))
            ->_addBreadcrumb(__('Customers'), __('Customers'))
            ->_addBreadcrumb(__('By Subscribe Products'), __('By Subscribe Products'));

        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Product Subscribe Report'));
        $this->_view->renderLayout();
    }
}