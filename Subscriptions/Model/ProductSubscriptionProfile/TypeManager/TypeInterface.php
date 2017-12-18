<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Quote\Api\Data\CartItemInterface;

/**
 * Product manager interface.
 */
interface TypeInterface
{
    /**
     * Updates subscription products buy requests.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     * @return array
     */
    public function modifyBuyRequests(array $products);

    /**
     * Returns full product subscription price.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $productData
     * @return string
     */
    public function getSubscriptionCustomPrice(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        array $productData
    );

    /**
     * Returns product subscription price.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $productData
     * @return float|string
     */
    public function getSubscriptionPrice(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        array $productData
    );

    /**
     * Return product object data.
     *
     * @param SaleableInterface $product
     * @param array|null $arguments
     * @return DataObject
     */
    public function getProductDataObject(SaleableInterface $product, array $arguments = null);

    /**
     * Check if billing frequency exist for products.
     *
     * @param string|int $billingFrequency
     * @param array $productIds
     * @return bool
     */
    public function checkFrequencyExistanse($billingFrequency, array $productIds);

    /**
     * Return additional data from CartItemInterface.
     * @param CartItemInterface $item
     * @return mixed
     */
    public function getAdditionalData(CartItemInterface $item);
}
