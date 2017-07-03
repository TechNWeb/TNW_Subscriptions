<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create\Product;

use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;
use Magento\Framework\Controller\ResultFactory;

class Save extends SubscriptionProfile
{
    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();

        $response = $this->getSubCreateModel()->addToSubscription($data);
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::SubscriptionProfile_create_product_save'
        );
    }
}
