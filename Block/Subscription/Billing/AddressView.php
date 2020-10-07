<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Billing;

use TNW\Subscriptions\Block\Subscription\Summary\Address;

/**
 * View billing address block
 */
class AddressView extends Address
{
    /**
     * @inheritdoc
     */
    public function getEditUrl($tabName = 'billing', array $params = [])
    {
        return 'javascript:';
    }
}
