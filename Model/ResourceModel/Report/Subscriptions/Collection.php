<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\Report\Subscriptions;

use Magento\Sales\Model\ResourceModel\Report\Order\Collection as BaseCollection;

/**
 * Report subscription collection
 */
class Collection extends BaseCollection
{
    /**
     * Aggregated Data Table
     *
     * @var string
     */
    protected $_aggregationTable = 'tnw_subscriptions_sales_order_aggregated_created';

    /**
     * {@inheritDoc}
     */
    public function getAggregatedColumns()
    {
        if (!$this->_aggregatedColumns) {
            $columns = [
                'orders_count',
                'total_qty_ordered',
                'total_qty_invoiced',
                'total_income_amount',
                'total_revenue_amount',
                'total_profit_amount',
                'total_invoiced_amount',
                'total_paid_amount',
                'total_refunded_amount',
                'total_tax_amount',
                'total_tax_amount_actual',
                'total_shipping_amount',
                'total_shipping_amount_actual',
                'total_discount_amount',
                'total_discount_amount_actual',
                'total_canceled_amount'
            ];
            $this->_aggregatedColumns = array_reduce($columns, function ($aggregated, $column) {
                $aggregated[$column] = "sum({$column})";
                return $aggregated;
            }, []);
        }

        return $this->_aggregatedColumns;
    }
}
