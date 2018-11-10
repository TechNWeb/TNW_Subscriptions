<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;

class QuoteEntityFilter implements CustomFilterInterface
{
    /**
     * Apply Custom Filter to Collection
     *
     * @param Filter $filter
     * @param AbstractDb $collection
     *
     * @return bool Whether the filter was applied
     * @since 100.2.0
     */
    public function apply(Filter $filter, AbstractDb $collection)
    {
        $collection->addFieldToFilter(
            "main_table.{$filter->getField()}",
            [$filter->getConditionType() => $filter->getValue()]
        );

        return true;
    }
}
