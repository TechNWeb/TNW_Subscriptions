<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductSubscriptionProfile;

use TNW\Subscriptions\Api\ProductSubscriptionProfileAttributeRepositoryInterface;

/**
 * Attribute repository
 */
class AttributeRepository implements ProductSubscriptionProfileAttributeRepositoryInterface
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    protected $eavAttributeRepository;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $eavAttributeRepository
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Eav\Api\AttributeRepositoryInterface $eavAttributeRepository,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->eavAttributeRepository = $eavAttributeRepository;
        $this->eavConfig = $eavConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        return $this->eavAttributeRepository->getList(
            \TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY,
            $searchCriteria
        );
    }

    /**
     * {@inheritdoc}
     */
    public function get($attributeCode)
    {
        return $this->eavAttributeRepository->get(
            \TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY,
            $attributeCode
        );
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getCustomAttributesMetadata($dataObjectClassName = null)
    {
        $defaultAttributeSetId = $this->eavConfig
            ->getEntityType(\TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY)
            ->getDefaultAttributeSetId();
        $searchCriteria = $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('attribute_set_id')
                    ->setValue($defaultAttributeSetId)
                    ->create(),
            ]
        );

        return $this->getList($searchCriteria->create())->getItems();
    }
}
