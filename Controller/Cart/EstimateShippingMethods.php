<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Cart;

use Magento\Framework\App\Action;
use Magento\Framework\App\ResponseInterface;

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
            ->setJsonData('[{"carrier_code":"flatrate","method_code":"flatrate","carrier_title":"Flat Rate","method_title":"Fixed","amount":15,"base_amount":15,"available":true,"error_message":"","price_excl_tax":15,"price_incl_tax":15},{"carrier_code":"tablerate","method_code":"bestway","carrier_title":"Best Way","method_title":"Table Rate","amount":5,"base_amount":5,"available":true,"error_message":"","price_excl_tax":5,"price_incl_tax":5}]');
    }
}
