<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile;

use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\ConfigurableFactory;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\SimpleFactory;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\TypeInterface;

/**
 * Resolve product type factory
 */
class ProductTypeFactoryResolver
{
    /**
     * Factory for creating product manager for simple product types.
     *
     * @var SimpleFactory
     */
    private $simpleFactory;

    /**
     * Factory for creating product manager for configurable product type.
     *
     * @var ConfigurableFactory
     */
    private $configurableFactory;


    /**
     * @param SimpleFactory $simpleFactory
     * @param ConfigurableFactory $configurableFactory
     */
    public function __construct(
        SimpleFactory $simpleFactory,
        ConfigurableFactory $configurableFactory
    ) {
        $this->simpleFactory = $simpleFactory;
        $this->configurableFactory = $configurableFactory;
    }

    /**
     * Returns product manager by product type.
     *
     * @param string $type
     * @return TypeInterface
     * @throws \InvalidArgumentException
     */
    public function getProductManagerByType($type)
    {
        switch ($type) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                $result = $this->simpleFactory->create();
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $result = $this->configurableFactory->create();
                break;
            default:
                throw new \InvalidArgumentException(__('Unsupported product type -' . $type));
                break;
        }

        return $result;
    }
}
