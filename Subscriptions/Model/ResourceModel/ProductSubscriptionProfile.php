<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel;

/**
 * Product subscription profile resource model.
 */
class ProductSubscriptionProfile extends \Magento\Eav\Model\Entity\AbstractEntity
{
    /**
     * @inheritdoc
     */
    public function getEntityType()
    {
        if (empty($this->_type)) {
            $this->setType(\TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY);
        }

        return parent::getEntityType();
    }
}
