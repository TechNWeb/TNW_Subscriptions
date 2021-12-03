<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\BillingFrequency\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Class Collection - BillingFrequency Grid Resource
 *
 * TODO: this should be removed in favor of vritual class when the website_id oclumn is removed from billing
 * frequency table *
 */
class Collection extends SearchResult
{
    /**
     * Initialization here
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setFlag('admin_gws_filtered', true);
    }
}
