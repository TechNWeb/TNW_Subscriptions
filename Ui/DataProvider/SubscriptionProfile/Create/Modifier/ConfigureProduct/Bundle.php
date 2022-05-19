<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\ConfigureProduct;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\DataObject;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\MultiSelect;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\EditProductOptions;

/**
 * DataProvider modifier on products configure form for bundle products.
 */
class Bundle extends Base
{
    /** Current product type */
    const PRODUCT_TYPE = Type::TYPE_BUNDLE;

    /** Options container prefix */
    const CONTAINER_PREFIX = 'bundle_option';

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        if ($this->isUsedModifier()) {
            $options = $this->getOptions();

            $data[EditProductOptions::FORM_DATA_VALUE][self::CONTAINER_PREFIX] = $options[self::CONTAINER_PREFIX];
            $data[EditProductOptions::FORM_DATA_VALUE][self::CONTAINER_PREFIX]['qty']
                = $options[self::CONTAINER_PREFIX . '_qty'];
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
     * Return bundle options meta data.
     *
     * @return array
     */
    private function getAttributesMeta()
    {
        $result = [];
        $iterator = 0;
        $bundleOptions = $this->getBundleOptions();

        foreach ($bundleOptions as $bundleOption) {
            $selections  = $bundleOption->getSelections();
            $selectionData = [
                'label' => $bundleOption->getTitle()
            ];
            $required = $bundleOption->getRequired() === '1';
            $isSingleChoice = $bundleOption->getType() === 'select' || $bundleOption->getType() === 'radio';
            if (!$required) {
                $selectionData['options'][] = [
                    'value' => '',
                    'label' => __('None')
                ];
            }
            foreach ($selections as $selection) {
                $selectionData['options'][] = [
                    'value' => $selection->getSelectionId(),
                    'label' => ($isSingleChoice ? '' : (float)$selection->getSelectionQty() . ' x ')
                        . $selection->getName()
                ];
            }
            $iterator++;
            $result[self::CONTAINER_PREFIX . $bundleOption->getId()] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => $selectionData['label'],
                            'collapsible' => false,
                            'componentType' => Field::NAME,
                            'formElement' => $isSingleChoice ? Select::NAME : MultiSelect::NAME,
                            'dataScope' => $bundleOption->getId(),
                            'sortOrder' => $iterator,
                            'options' => $selectionData['options'],
                            'validation' => [
                                'required-entry' => $required
                            ]
                        ],
                    ],
                ],
            ];
            if ($isSingleChoice) {
                $iterator++;
                $result[self::CONTAINER_PREFIX . '_qty' . $bundleOption->getId()] = [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Qty'),
                                'dataType' => 'text',
                                'collapsible' => false,
                                'componentType' => Field::NAME,
                                'formElement' => Input::NAME,
                                'dataScope' => 'qty.' . $bundleOption->getId(),
                                'sortOrder' => $iterator,
                                'value' => $bundleOption->getSelections()[0]->getSelectionQty() * 1,
                                'disabled' => !$bundleOption->getSelections()[0]->getSelectionCanChangeQty(),
                                'validation' => [
                                    'required-entry' => $required,
                                    'validate-zero-or-greater' => !$required,
                                    'validate-greater-than-zero' => $required
                                ]
                            ],
                        ],
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * Get options for bundle product
     * @return DataObject[]
     */
    public function getBundleOptions()
    {
        $product = $this->getProduct();
        /** @var \Magento\Bundle\Model\Product\Type $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $typeInstance->setStoreFilter($product->getStoreId(), $product);

        $optionCollection = $typeInstance->getOptionsCollection($product);

        $selectionCollection = $typeInstance->getSelectionsCollection(
            $typeInstance->getOptionsIds($product),
            $product
        );

        return $optionCollection->appendSelections(
            $selectionCollection,
            true,
            true,
        );
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
