<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\Report\Subscriptions\UpdatedAt;

use TNW\Subscriptions\Model\ResourceModel\Report\Subscriptions\Collection as CreatedAtCollection;

/**
 * Report subscription updated_at collection
 */
class Collection extends CreatedAtCollection
{
    /**
     * Aggregated Data Table
     *
     * @var string
     */
    protected $_aggregationTable = 'tnw_subscriptions_sales_order_aggregated_updated';
}
