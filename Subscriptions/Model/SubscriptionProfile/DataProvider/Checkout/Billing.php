<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\ShippingAndBilling;

/**
 * Class Billing data provider.
 */
class Billing extends ShippingAndBilling
{
    /**
     * @return array|mixed
     */
    public function getConfigData()
    {
        $configData = parent::getConfigData();

        $configData['submit_url'] = $this->urlBuilder->getUrl('*/subscription_create/process');

        return $configData;
    }
}
