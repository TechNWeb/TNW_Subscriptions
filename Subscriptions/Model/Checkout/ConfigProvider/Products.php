<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as CatalogProduct;

/**
 * Products config for Cart.
 */
class Products implements ConfigProviderInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        //todo config for product
        return [];
    }
}
