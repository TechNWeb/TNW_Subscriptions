<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create\Product\Modified;

use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\AbstractSave;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Saves modified subscription item.
 */
class Save extends AbstractSave
{
    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $result = $this->getSaveProcessor()->processSave(
            $this->getRequest()->getParams()
        );
        $response = $this->getJsonResponse($result);
        if (!$result){
            $response['objects_count'] = count(
                $this->getSubCreateModel()->getSession()->getSubQuoteIds()
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setData($response);
    }
}
