<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\Customer;

class Profiles extends \Magento\Customer\Controller\Adminhtml\Index
{
    /**
     * Customer orders grid
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        $resultLayout = $this->resultLayoutFactory->create();
        return $resultLayout;
    }
}
