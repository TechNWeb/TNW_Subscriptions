<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\Product;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;

/**
 * Class AddBillingFrequencyIdFieldToCollection - dataProvider
 */
class AddBillingFrequencyIdFieldToCollection implements AddFilterToCollectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function addFilter(Collection $collection, $field, $condition = null)
    {
        if (isset($condition['eq']) && $condition['eq']) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection  */
            $collection->getSelect()->where('sub_table.billing_frequency_id = ?', $condition['eq']);
        }
    }
}
