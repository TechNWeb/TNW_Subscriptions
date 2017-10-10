<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Eav\Model\ResourceModel\Entity;

use TNW\Subscriptions\Model;

/**
 * Eav attribute save interceptor.
 */
class Attribute
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory
     */
    private $groupCollectionFactory;

    /**
     * Attribute constructor.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory
    ) {
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * Set default attribute set and attribute group for Subscription Profile attributes
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $subject
     * @param \Magento\Eav\Model\Entity\Attribute $object
     * @return void
     */
    public function beforeSave(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $subject,
        $object
    ) {
        if (strcasecmp($object->getEntityType()->getEntityTypeCode(), Model\SubscriptionProfile::ENTITY) === 0) {
            // If attribute set is not specified we set Default attribute set for this entity type
            $attributeSetId = $object->getAttributeSetId();
            if (empty($attributeSetId)) {
                $attributeSetId = $object->getEntityType()->getDefaultAttributeSetId();
                $object->setAttributeSetId($attributeSetId);
            }

            // If attribute group is not specified we set 'additional-information' group
            $attributeGroupId = $object->getAttributeGroupId();
            if (empty($attributeGroupId)) {
                $groupCollection = $this->groupCollectionFactory->create()
                    ->setAttributeSetFilter($attributeSetId)
                    ->addFieldToFilter('attribute_group_code', Model\SubscriptionProfile::DEFAULT_GROUP_CODE)
                    ->setPageSize(1)
                    ->load();

                $group = $groupCollection->getFirstItem();
                $object->setAttributeGroupId($group->getId());
            }
        }

        if (strcasecmp($object->getEntityType()->getEntityTypeCode(), Model\ProductSubscriptionProfile::ENTITY) === 0) {
            // If attribute set is not specified we set Default attribute set for this entity type
            $attributeSetId = $object->getAttributeSetId();
            if (empty($attributeSetId)) {
                $attributeSetId = $object->getEntityType()->getDefaultAttributeSetId();
                $object->setAttributeSetId($attributeSetId);
            }

            // If attribute group is not specified we set 'additional-information' group
            $attributeGroupId = $object->getAttributeGroupId();
            if (empty($attributeGroupId)) {
                $groupCollection = $this->groupCollectionFactory->create()
                    ->setAttributeSetFilter($attributeSetId)
                    ->addFieldToFilter('attribute_group_code', Model\ProductSubscriptionProfile::DEFAULT_GROUP_CODE)
                    ->setPageSize(1)
                    ->load();

                $group = $groupCollection->getFirstItem();
                $object->setAttributeGroupId($group->getId());
            }
        }
    }
}
