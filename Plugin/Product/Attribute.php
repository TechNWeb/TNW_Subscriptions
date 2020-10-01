<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Product;

use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;
use TNW\Subscriptions\Model\ProductDefaultAttributes;

/**
 * Eav attribute interceptor
 */
class Attribute
{
    /**
     * Product default attributes helper.
     *
     * @var ProductDefaultAttributes
     */
    private $productDefaultAttributes;

    /**
     * Default values cache for subscriptions attributes.
     *
     * @var array
     */
    private $defaultValues;

    /**
     * Magento\Eav\Model\Entity\Type instance holder.
     *
     * @var Type
     */
    private $eavEntityType;

    /**
     * Factory for creating group collection holder.
     *
     * @var CollectionFactory
     */
    private $groupCollectionFactory;

    /**
     * @param ProductDefaultAttributes $productDefaultAttributes
     * @param Type $eavEntityType
     * @param CollectionFactory $groupCollectionFactory
     */
    public function __construct(
        ProductDefaultAttributes $productDefaultAttributes,
        Type $eavEntityType,
        CollectionFactory $groupCollectionFactory
    ) {
        $this->productDefaultAttributes = $productDefaultAttributes;
        $this->eavEntityType = $eavEntityType;
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * Set default value for subscriptions attributes from config.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $subject
     * @param \Magento\Catalog\Model\Product $result
     * @return \Magento\Catalog\Model\Product
     */
    public function afterGetDefaultValue(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $subject,
        $result
    ) {
        if ($subject->getEntityType()->getEntityTypeCode() == \Magento\Catalog\Model\Product::ENTITY) {
            $defaultValues = $this->getDefaultValues();
            if (array_key_exists($subject->getAttributeCode(), $defaultValues)) {
                $result = $defaultValues[$subject->getAttributeCode()];
            }
        }

        return $result;
    }

    /**
     * Get default values for subscription product attributes.
     * This method implement cache for this default values.
     *
     * @return array
     */
    private function getDefaultValues()
    {
        if ($this->defaultValues === null) {
            $this->defaultValues = $this->productDefaultAttributes->getDefaultValues();
        }

        return $this->defaultValues;
    }
}
