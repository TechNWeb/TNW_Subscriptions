<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\ConfigureProduct;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\EditProductOptions;

/**
 * DataProvider modifier on products configure form for configurable products.
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
    public function modifyData(array $data)
    {
        if ($this->isUsedModifier()) {
            $options = $this->getOptions();
            $optionsData = [];

            if (is_array($options)) {
                foreach ($options as $attributeId => $option) {
                    $key = $attributeId;
                    $optionsData[$key] = $option;
                }
            }

            $data[EditProductOptions::FORM_DATA_VALUE][self::CONTAINER_PREFIX] = $optionsData;
        }

        return $data;
    }


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
                                    'sortOrder' => 20,
                                    'dataScope' => self::CONTAINER_PREFIX,
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

        foreach ($allowedAttributes as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            $attributeId = $productAttribute->getId();
            $attributeData = [
                'attributeId' => $attributeId,
                'label' => $productAttribute->getStoreLabel($storeId)
            ];

            foreach ($attribute->getOptions() as $option) {
                $attributeData['options'][] = [
                    'value' => $option['value_index'],
                    'label' => $option['store_label'],
                ];
            }
            $iterator++;
            $result[self::CONTAINER_PREFIX . $attribute->getAttributeId()] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => $attributeData['label'],
                            'collapsible' => false,
                            'componentType' => Field::NAME,
                            'formElement' => Select::NAME,
                            'dataScope' => $attribute->getAttributeId(),
                            'sortOrder' => $iterator,
                            'options' => $attributeData['options'],
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
     * Return product options from request.
     *
     * @return mixed
     */
    private function getOptions()
    {
        return $this->formContext->getRequest()->getParam('options', []);
    }
}
