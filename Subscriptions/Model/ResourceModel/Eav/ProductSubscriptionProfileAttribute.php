<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\Eav;

use Magento\Eav\Model\Entity\Attribute;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileAttributeInterface;

/**
 * Product subscription profile attribute model
 */
class ProductSubscriptionProfileAttribute extends Attribute implements ProductSubscriptionProfileAttributeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getIsVisibleOnFront()
    {
        return $this->getData(self::IS_VISIBLE_ON_FRONT);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsVisibleOnFront($isVisibleOnFront)
    {
        return $this->setData(self::IS_VISIBLE_ON_FRONT, $isVisibleOnFront);
    }
}
