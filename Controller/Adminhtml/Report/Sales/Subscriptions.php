<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\Report\Sales;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Reports\Controller\Adminhtml\Report\Sales as BaseController;
use TNW\Subscriptions\Model\ResourceModel\Report\Order;

/**
 * Subscriptions report admin controller
 */
class Subscriptions extends BaseController implements HttpGetActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->_showLastExecutionTime(Order::REPORT_SUBSCRIPTION_FLAG_CODE, 'subscriptions');

        $this->_initAction()->_setActiveMenu(
            'Magento_Reports::report_sales_subscriptions'
        )->_addBreadcrumb(
            __('Subscriptions Report'),
            __('Subscriptions Report')
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Subscriptions Report'));

        $this->_view->renderLayout();
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TNW_Subscriptions::report_sales_subscriptions');
    }
}
