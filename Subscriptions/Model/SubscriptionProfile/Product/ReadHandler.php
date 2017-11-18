<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Product;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfileRepository;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;

/**
 * Loads profile products and sets to subscription profile.
 */
class ReadHandler implements ExtensionInterface
{

    /**
     * @var ProductSubscriptionProfileRepository
     */
    private $productProfileRepository;


    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * ReadHandler constructor.
     * @param ProductSubscriptionProfileRepository $productProfileRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        ProductSubscriptionProfileRepository $productProfileRepository,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->productProfileRepository = $productProfileRepository;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * @param SubscriptionProfileInterface $entity
     * @param array $arguments
     * @return SubscriptionProfileInterface
     */
    public function execute($entity, $arguments = [])
    {
        $this->criteriaBuilder->addFilter(
            ProductSubscriptionProfileInterface::SUBSCRIPTION_PROFILE_ID,
            $entity->getId()
        );
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->create();

        $products = $this->productProfileRepository->getList($searchCriteria)->getItems();
        $entity->setProducts($products);
        $entity->setVisibleProducts($this->getVisibleProducts($products));
        return $entity;
    }

    /**
     * Returns visible profile products.
     *
     * @param ProductSubscriptionProfileInterface[] $products
     * @return ProductSubscriptionProfileInterface[]
     */
    private function getVisibleProducts(array $products)
    {
        return array_filter(
            $products,
            function (ProductSubscriptionProfileInterface $product) {
                return !$product->getParentId();
            }
        );
    }
}
