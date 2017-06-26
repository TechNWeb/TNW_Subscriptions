<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel;

use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductBillingFrequency extends AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE,
            'id'
        );
    }
}
