<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Save shipping methods processor.
 */
class ShippingMethods extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        if (isset($data['shipping_method'])) {
            $this->errors = $this->getSubCreateModel()
                ->setShippingMethods($data['shipping_method']);
        }
    }
}
