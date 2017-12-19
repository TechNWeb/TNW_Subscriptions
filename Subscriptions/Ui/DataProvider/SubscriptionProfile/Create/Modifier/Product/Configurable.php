<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Product;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;

/**
 * DataProvider modifier on add to subscription form for configurable products.
 */
class Configurable extends Base
{
    /** Current product type */
    const PRODUCT_TYPE = ConfigurableProduct::TYPE_CODE;

    /** Options container prefix */
    const CONTAINER_PREFIX = 'super_attribute';

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        if ($this->isUsedModifier()) {
            $meta = array_merge_recursive(
                $meta,
                [
                    'options' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => __('Product Options'),
                                    'collapsible' => false,
                                    'componentType' => Fieldset::NAME,
                                    'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                                    'sortOrder' => 1,
                                    'dataScope' => self::CONTAINER_PREFIX,
                                    'additionalClasses' => 'product-options',
                                ],
                            ],
                        ],
                        'children' => $this->getAttributesMeta(),
                    ],
                ]
            );
        }

        return $meta;
    }

    /**
     * Return super attributes meta data.
     *
     * @return array
     */
    private function getAttributesMeta()
    {
        $result = [];
        $iterator = 0;
        $allowedAttributes = $this->getAllowAttributes();
        /** @var MagentoProduct $product */
        $product = $this->getProduct();
        $storeId = $product->getStoreId();
        $superAttributes = $this->getSuperAttributes();

        foreach ($allowedAttributes as $attribute) {
            $attributeData = [];
            $productAttribute = $attribute->getProductAttribute();
            $attributeId = $productAttribute->getId();

            if (isset($superAttributes[$attributeId])) {
                foreach ($attribute->getOptions() as $option) {
                    if ($option['value_index'] == $superAttributes[$attributeId]) {
                        $optionLabel = $option['store_label'];
                        break;
                    }
                }

                $attributeData = [
                    'attributeLabel' => $productAttribute->getStoreLabel($storeId),
                    'optionLabel' => $optionLabel,
                ];
            }

            $iterator++;
            $result[self::CONTAINER_PREFIX . $attribute->getAttributeId()] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => $attributeData['attributeLabel'],
                            'collapsible' => false,
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                            'sortOrder' => $iterator,
                            'value' => $attributeData['optionLabel'],
                        ],
                    ],
                ],
            ];
        }

        return $result;
    }

    /**
     * Return current product super attributes.
     *
     * @return \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute[]
     */
    private function getAllowAttributes()
    {
        return $this->getProduct()->getTypeInstance()->getConfigurableAttributes($this->getProduct());
    }

    /**
     * Return selected attributes and their ids from request.
     *
     * @return mixed
     */
    private function getSuperAttributes()
    {
        return $this->formContext->getRequest()->getParam('super_attribute');
    }
}
