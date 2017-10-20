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
        $error = false;
        $message = '';
        $data = $this->getRequest()->getParams();
        try {
            $this->getSubCreateModel()->addToSubscription($data);
        } catch (\Exception $e) {
            $error = true;
            $message = $e->getMessage();
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(
            $this->getJsonResponse($error, $message)
        );
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

    /**
     * @param $response
     * @return mixed
     */
    private function getJsonResponse($error, $message)
    {
        return [
            'error' => $error,
            'message' => $message
        ];
    }
}
