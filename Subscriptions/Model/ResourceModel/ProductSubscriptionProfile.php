<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel;

use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;

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

    /**
     * Get profile ids by product ids
     *
     * @param array $productIds
     * @return array [product_id(int), profile_id(int), profile_status(int)]
     */
    public function getProfileStatusByProductIds($productIds = [])
    {
        $result = [];

        if (is_array($productIds)) {
            $select = $this->getConnection()->select();
            $select->from(
                [
                    'main' => $this->getTable(\TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY_TABLE)
                ],
                [
                    'product_id' => 'main.' . ProductSubscriptionProfileInterface::MAGENTO_PRODUCT_ID,
                    'profile_id' => 'main.' . ProductSubscriptionProfileInterface::SUBSCRIPTION_PROFILE_ID
                ]
            );
            $select->join(
                ['profile' => $this->getTable(\TNW\Subscriptions\Model\SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY)],
                sprintf(
                    "main.%s = profile.%s",
                    ProductSubscriptionProfileInterface::SUBSCRIPTION_PROFILE_ID,
                    ProductSubscriptionProfileInterface::ID
                ),
                ['profile.status AS profile_status']
            );
            $select->where(
                sprintf(
                    'main.%s IN (?)',
                    ProductSubscriptionProfileInterface::MAGENTO_PRODUCT_ID
                ),
                $productIds
            );
            $result = $this->getConnection()->query($select)->fetchAll();
        }

        return $result ? : [];
    }
}
