<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\EditProduct;

use Magento\Catalog\Model\Product as MagentoProduct;

/**
 * Base dataProvider modifier on add to subscription form for modify subscription data.
 */
class Base implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * Current product type
     */
    const PRODUCT_TYPE = '';

    /**
     * @var \Magento\Quote\Model\Quote\Item|\TNW\Subscriptions\Model\ProductSubscriptionProfile
     */
    private $item;

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Checks if current modifier can be used. Depends on product type.
     *
     * @return bool
     */
    protected function isUsedModifier()
    {
        if ($this->getProduct()) {
            return $this->getProduct()->getTypeId() === $this::PRODUCT_TYPE;
        }

        return false;
    }

    /**
     * Return current product.
     *
     * @return MagentoProduct
     */
    public function getProduct()
    {
        return $this->getItem()->getProduct();
    }

    /**
     * Set current quote item.
     *
     * @param \Magento\Quote\Model\Quote\Item|\TNW\Subscriptions\Model\ProductSubscriptionProfile $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * Return current quote item.
     *
     * @return \Magento\Quote\Model\Quote\Item||\TNW\Subscriptions\Model\ProductSubscriptionProfile
     */
    protected function getItem()
    {
        return $this->item;
    }
}
