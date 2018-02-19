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
     * @var \Magento\Framework\EntityManager\EntityManager
     */
    private $entityManager;

    /**
     * SubscriptionProfile constructor.
     * @param \Magento\Eav\Model\Entity\Context $context
     * @param \Magento\Framework\EntityManager\EntityManager $entityManager
     * @param array $data
     */
    public function __construct(
        \Magento\Eav\Model\Entity\Context $context,
        \Magento\Framework\EntityManager\EntityManager $entityManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->entityManager = $entityManager;
    }

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
    public function getProfileStatusByProductIds(array $productIds = [])
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
                ['profile_status' => 'profile.status']
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

    /**
     * Reset firstly loaded attributes
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param integer $entityId
     * @param array|null $attributes
     * @return $this
     */
    public function load($object, $entityId, $attributes = [])
    {
        $this->loadAttributesMetadata($attributes);
        $this->entityManager->load($object, $entityId);
        return $this;
    }

    /**
     * Save entity's attributes into the object's resource
     *
     * @param  \Magento\Framework\Model\AbstractModel $object
     * @return $this
     * @throws \Exception
     */
    public function save(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->entityManager->save($object);
        return $this;
    }
}
