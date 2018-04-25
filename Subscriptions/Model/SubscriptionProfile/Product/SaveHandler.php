<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Product;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfileRepository;

/**
 * Saves profile products after saving subscription profile.
 */
class SaveHandler implements ExtensionInterface
{
    /**
     * @var ProductSubscriptionProfileRepository
     */
    private $productProfileRepository;

    /**
     * SaveHandler constructor.
     * @param ProductSubscriptionProfileRepository $productProfileRepository
     */
    public function __construct(
        ProductSubscriptionProfileRepository $productProfileRepository
    ) {
        $this->productProfileRepository = $productProfileRepository;
    }

    /**
     * @param SubscriptionProfileInterface $entity
     * @param array $arguments
     * @return SubscriptionProfileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($entity, $arguments = [])
    {
        $products = $entity->getProducts();
        usort($products, function ($a, $b) {
            $countA = count($a->getChildren());
            $countB = count($b->getChildren());

            if ($countA === $countB) {
                return 0;
            }

            return ($countA < $countB) ? 1 : -1;
        });

        foreach ($products as $product) {
            $product->setSubscriptionProfileId($entity->getId());
            $this->productProfileRepository->save($product);

            foreach ($product->getChildren() as $child) {
                $child->setParentId($product->getId());
            }
        }

        return $entity;
    }
}
