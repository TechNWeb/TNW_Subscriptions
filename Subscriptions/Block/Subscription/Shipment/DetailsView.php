<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Shipment;

use TNW\Subscriptions\Block\Subscription\Summary\Shipping\Details;

/**
 * View shipping details block
 */
class DetailsView extends Details
{
    /**
     * @inheritdoc
     */
    public function getEditUrl($tabName = 'shipment', array $params = [])
    {
        return 'javascript:';
    }
}
