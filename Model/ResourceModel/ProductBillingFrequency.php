<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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

    /**
     * Checks if there is a record with current billing frequency id
     *
     * @param $id
     * @return array
     */
    public function isBillingFrequencyAllowedToProduct($id)
    {
        $connection = $this->getConnection();
        $sql = $connection->select()
            ->from(
                ['main' => $this->getTable(ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE)]
        )->where('main.' . ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID . '=?', $id);
        return $this->getConnection()->fetchCol($sql);
    }
}
