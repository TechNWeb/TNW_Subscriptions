<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription\Customer\Account;

use Magento\Framework\Controller\ResultFactory;
use TNW\Subscriptions\Controller\Subscription\AbstractSave;

/**
 * Saves modified subscription profile.
 */
class Save extends AbstractSave
{

    public function execute()
    {
        $result = [];
        $response = $this->getJsonResponse($result);

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setData($response);
    }
}
