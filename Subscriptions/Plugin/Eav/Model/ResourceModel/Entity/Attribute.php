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
    const GROUP_SIZE = 1;

    /**
     * @var \Magento\Eav\Api\AttributeGroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $allowedGroup = [
        Model\SubscriptionProfile::ENTITY => Model\SubscriptionProfile::DEFAULT_GROUP_CODE,
        Model\ProductSubscriptionProfile::ENTITY => Model\ProductSubscriptionProfile::DEFAULT_GROUP_CODE
    ];

    /**
     * Attribute constructor.
     * @param \Magento\Eav\Api\AttributeGroupRepositoryInterface $groupRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Eav\Api\AttributeGroupRepositoryInterface $groupRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Set default attribute set and attribute group for Subscription Profile attributes
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $subject
     * @param \Magento\Eav\Model\Entity\Attribute $object
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSave(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $subject,
        $object
    ) {
        if (!$object->isObjectNew()) {
            return;
        }

        if (!isset($this->allowedGroup[$object->getEntityType()->getEntityTypeCode()])) {
            return;
        }

        // If attribute set is not specified we set Default attribute set for this entity type
        $attributeSetId = $object->getAttributeSetId();
        if (empty($attributeSetId)) {
            $attributeSetId = $object->getEntityType()->getDefaultAttributeSetId();
            $object->setAttributeSetId($attributeSetId);
        }

        // If attribute group is not specified we set 'additional-information' group
        $attributeGroupId = $object->getAttributeGroupId();
        if (empty($attributeGroupId)) {
            $this->searchCriteriaBuilder
                ->addFilter('attribute_set_id', $attributeSetId)
                ->addFilter(
                    'attribute_group_code',
                    $this->allowedGroup[$object->getEntityType()->getEntityTypeCode()]
                )
                ->setPageSize(self::GROUP_SIZE);

            $items = $this->groupRepository
                ->getList($this->searchCriteriaBuilder->create())
                ->getItems();

            if (!empty($items)) {
                $object->setAttributeGroupId(reset($items)->getAttributeGroupId());
            }
        }
    }
}
