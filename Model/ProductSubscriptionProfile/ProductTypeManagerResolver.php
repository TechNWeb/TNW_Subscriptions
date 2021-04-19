<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductSubscriptionProfile;

use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\ConfigurableFactory;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\GroupedFactory;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\SimpleFactory;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\TypeInterface;

/**
 * Resolve product type factory
 */
class ProductTypeManagerResolver
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
     * Factory for creating product manager for grouped product types.
     *
     * @var GroupedFactory
     */
    private $groupedFactory;

    /**
     * @param SimpleFactory $simpleFactory
     * @param ConfigurableFactory $configurableFactory
     * @param GroupedFactory $groupedFactory
     */
    public function __construct(
        SimpleFactory $simpleFactory,
        ConfigurableFactory $configurableFactory,
        GroupedFactory $groupedFactory
    ) {
        $this->simpleFactory = $simpleFactory;
        $this->configurableFactory = $configurableFactory;
        $this->groupedFactory = $groupedFactory;
    }

    /**
     * Returns product manager by product type.
     *
     * @param string $type
     * @return TypeInterface
     * @throws \InvalidArgumentException
     */
    public function resolve($type)
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
            case \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE:
                $result = $this->groupedFactory->create();
                break;
            default:
                throw new \InvalidArgumentException(__('Unsupported product type -' . $type));
        }

        return $result;
    }
}
