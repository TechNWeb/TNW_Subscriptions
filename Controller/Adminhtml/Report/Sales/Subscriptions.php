<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\Report\Sales;

use Magento\Backend\App\Action;

/**
 * Class Subscriptions -controller for report form/grid
 */
class Subscriptions extends Action
{
    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TNW_Subscriptions::report_sales_subscriptions');
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

        $this->_setActiveMenu('Magento_Reports::report_sales_subscriptions')
            ->_addBreadcrumb(__('Reports'), __('Reports'))
            ->_addBreadcrumb(__('Sales'), __('Sales'))
            ->_addBreadcrumb(__('Subscriptions'), __('Subscriptions'));

        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Sales Subscriptions Report'));
        $this->_view->renderLayout();
    }
}
