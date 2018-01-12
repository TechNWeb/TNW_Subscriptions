<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use TNW\Subscriptions\Model\ProductDefaultAttributes;

/**
 * Data provider for subscription product attributes default values.
 */
class DefaultValues extends AbstractModifier
{
    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * Product default attributes helper.
     *
     * @var ProductDefaultAttributes
     */
    private $productDefaultAttributes;

    /**
     * @param LocatorInterface $locator
     * @param ProductDefaultAttributes $productDefaultAttributes
     */
    public function __construct(
        LocatorInterface $locator,
        ProductDefaultAttributes $productDefaultAttributes
    ) {
        $this->locator = $locator;
        $this->productDefaultAttributes = $productDefaultAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->locator->getProduct();
        $productId = $product->getId();
        if (!$productId) {
            $dataDefault = $this->productDefaultAttributes->getDefaultValues($product);
            $data[$productId][self::DATA_SOURCE_DEFAULT] =
                array_merge($data[$productId][self::DATA_SOURCE_DEFAULT], $dataDefault);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
