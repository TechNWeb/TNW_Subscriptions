<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;

/**
 * Resource class for credit memo item extension attribute.
 */
class CreditmemoItem extends AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            SalesExtensionAttributesInterface::CREDITMEMO_ITEM_EXTENSION_TABLE,
            SalesExtensionAttributesInterface::MAGENTO_ITEM_ID
        );
    }
}
