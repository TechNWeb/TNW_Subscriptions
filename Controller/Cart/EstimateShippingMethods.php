<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Cart;

use Magento\Framework\App\Action;
use Magento\Framework\App\ResponseInterface;

/**
 * EstimateShippingMethods
 * @deprecated
 */
class EstimateShippingMethods extends Action\Action
{
    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setJsonData('[]');
    }
}
