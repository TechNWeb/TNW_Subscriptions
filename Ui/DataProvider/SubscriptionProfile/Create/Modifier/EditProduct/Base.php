<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\EditProduct;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;

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
     * @var Item|ProductSubscriptionProfile
     */
    private $item;

    /**
     * Data providers form context.
     *
     * @var FormContext
     */
    private $formContext;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Base constructor.
     * @param FormContext $formContext
     * @param SerializerInterface $serializer
     */
    public function __construct(
        FormContext $formContext,
        SerializerInterface $serializer
    ) {
        $this->formContext = $formContext;
        $this->serializer = $serializer;
    }

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
     * @return MagentoProduct|null
     */
    public function getProduct()
    {
        $currentItem = $this->getItem();
        $product = $currentItem->getProduct();
        if (null === $product) {
            $productId = $currentItem->getProductId();
            if ($productId) {
                $product = $this->formContext
                    ->getProductRepository()
                    ->getById($productId, false, $currentItem->getStoreId());
            }
        }

        return $product;
    }

    /**
     * Set current quote item.
     *
     * @param Item|ProductSubscriptionProfile $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * Return current quote item.
     *
     * @return Item|ProductSubscriptionProfile
     */
    protected function getItem()
    {
        return $this->item;
    }
}
