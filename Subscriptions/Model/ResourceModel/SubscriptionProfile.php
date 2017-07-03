<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Eav\Model\Entity\AbstractEntity;

/**
 * Resource model for Subscription Profile.
 */
class SubscriptionProfile extends AbstractEntity
{
    /**
     * {@inheritdoc}
     */
    public function getEntityType()
    {
        if (empty($this->_type)) {
            $this->setType(\TNW\Subscriptions\Model\SubscriptionProfile::ENTITY);
        }

        return parent::getEntityType();
    }
}
