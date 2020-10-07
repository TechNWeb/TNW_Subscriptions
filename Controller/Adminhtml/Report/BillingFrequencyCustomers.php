<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\Report;

/**
 * Class BillingFrequencyCustomers - controller
 */
class BillingFrequencyCustomers extends \Magento\Backend\App\Action
{
    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TNW_Subscriptions::report_subscribe_billing_frequency_customers');
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
            ->_addBreadcrumb(__('Billing Frequencies'), __('Billing Frequencies'))
            ->_addBreadcrumb(__('By Customers'), __('By Customers'));

        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Customer Report'));
        $this->_view->renderLayout();
    }
}
