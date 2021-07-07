<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use TNW\Subscriptions\Api\Data\CustomerProductHistoryInterface;

/**
 * Class CustomerProductHistory - resource model
 */
class CustomerProductHistory extends AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(
            CustomerProductHistoryInterface::CUSTOMER_PRODUCT_HISTORY_TABLE,
            CustomerProductHistoryInterface::ID
        );
    }
}
