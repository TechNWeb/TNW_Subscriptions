<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\MetadataServiceInterface;

/**
 * Interface RepositoryInterface must be implemented in new model
 */
interface ProductSubscriptionProfileAttributeRepositoryInterface extends MetadataServiceInterface
{
    /**
     * Retrieve all attributes for entity type
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Catalog\Api\Data\CategoryAttributeSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Retrieve specific attribute
     *
     * @param string $attributeCode
     * @return \Magento\Catalog\Api\Data\CategoryAttributeInterface
     */
    public function get($attributeCode);
}
