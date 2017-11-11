<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create\Product;

use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;
use Magento\Framework\Controller\ResultFactory;

/**
 * Add to subscription controller
 */
class Save extends SubscriptionProfile
{
    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $error = true;
        $message = __('We can\'t add this item to your subscription shopping cart right now.');
        $data = $this->getRequest()->getParams();
        try {
            $result = $this->getSubCreateModel()->addToSubscription($data);
            if ($result) {
                $error = false;
                $message = '';
                $this->_getSession()->addSubQuote($result->getQuote());
            }
        } catch (\Exception $e) {
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
     * Returns response array.
     *
     * @param bool $error
     * @param string $message
     * @return array
     */
    private function getJsonResponse($error, $message)
    {
        return [
            'error' => $error,
            'message' => $message
        ];
    }
}
