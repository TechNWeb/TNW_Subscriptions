<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Controller for creating subscription profiles.
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
        $errors = $this->getSaveProcessor()->processSave(
            $this->getRequest()->getParams()
        );

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setData($this->getJsonResponse($errors));
    }
}
