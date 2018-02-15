<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Account;

/**
 * Class Shipping data provider.
 */
class Shipping extends Account
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

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = [];

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }
}
