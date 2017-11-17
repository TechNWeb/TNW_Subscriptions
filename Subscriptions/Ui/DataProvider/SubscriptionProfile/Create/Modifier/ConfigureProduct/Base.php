<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\ConfigureProduct;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Registry;

/**
 * \TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ConfigurableForm base modifier,
 */
class Base implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /** Current product type */
    const PRODUCT_TYPE = '';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var MagentoProduct
     */
    private $product;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry) {
        $this->registry = $registry;
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
        $product = $this->getProduct();

        return $product ? $product->getTypeId() == $this::PRODUCT_TYPE : false;
    }

    /**
     * Return current product.
     *
     * @return MagentoProduct
     */
    protected function getProduct()
    {
        if (!$this->product) {
            $this->product = $this->registry->registry('product');
        }

        return $this->product;
    }
}
