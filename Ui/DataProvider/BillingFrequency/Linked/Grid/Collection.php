<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Linked\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Linked products grid collection
 */
class Collection extends SearchResult
{
    /**
     * @inheritDoc
     */
    protected function _initSelect()
    {
        $this->addFilterToMap('magento_product_id', 'main_table.magento_product_id');
        $this->addFilterToMap('product_name', 'nametable.value');
        $this->addFilterToMap('original_price', 'pricetable.value');
        $this->addFilterToMap('status', 'statustable.value');
        return parent::_initSelect();
    }
}
