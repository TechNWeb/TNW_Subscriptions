<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create\Product\Modified;

use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Saves modified subscription item.
 */
class Save extends SubscriptionProfile
{
    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $objectId = $this->getRequest()->getParam('objectId', false);
        if ($objectId){
            $objectItemId = $this->getRequest()->getParam('objectItemId', false);
            if ($objectItemId){
                $remove = $this->getRequest()->getParam('remove', false);
                $requestData = $this->getRequest()->getParam('item_' . $objectItemId, false);
                try {
                    $this->getSubCreateModel()->modifySubscriptions($requestData, $objectId, $objectItemId, $remove);
                    $this->_getSession()->getSubQuoteIds();
                    $response = [
                        'error' => false,
                        'message' => '',
                        'objects_count' => count( $this->_getSession()->getSubQuoteIds())
                    ];
                }
                catch (\Exception $e) {
                    $response = $this->getErrorResponse(
                        $e->getMessage()
                    );
                }
            } else {
                $response = $this->getErrorResponse(
                    __('Object item id is not defined.')
                );
            }
        } else {
            $response = $this->getErrorResponse(
                __('Object id is not defined.')
            );
        }

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

    /**
     * Returns error response.
     *
     * @param string $message
     * @return array
     */
    private function getErrorResponse($message)
    {
        return [
            'error' => true,
            'message' => $message
        ];
    }
}
