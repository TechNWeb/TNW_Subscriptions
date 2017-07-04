<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Eav;

use TNW\Subscriptions\Model\ProductDefaultAttributes;

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
     * @param \Magento\Eav\Model\Attribute $subject
     * @return void
     */
    public function beforeSave(
        \Magento\Eav\Model\Attribute $subject
    ) {
        if ($subject->getEntityType()->getEntityTypeCode() == \TNW\Subscriptions\Model\SubscriptionProfile::ENTITY) {
            // If attribute set is not specified we set Default attribute set for this entity type
            $attributeSetId = $subject->getAttributeSetId();
            if (!$attributeSetId) {
                $attributeSetId = $subject->getEntityType()->getDefaultAttributeSetId();
                $subject->setAttributeSetId($attributeSetId);
            }

            // If attribute group is not specified we set 'additional-information' group
            $attributeGroupId = $subject->getAttributeGroupId();
            if (!$attributeGroupId) {
                $groupCollection = $this->groupCollectionFactory->create()
                    ->setAttributeSetFilter($attributeSetId)
                    ->addFieldToFilter('attribute_group_code', 'additional-information')
                    ->setPageSize(1)
                    ->load();

                $group = $groupCollection->getFirstItem();
                $subject->setAttributeGroupId($group->getId());
            }
        }
    }
}
