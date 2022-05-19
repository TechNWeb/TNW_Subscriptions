<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Product;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\DataObject;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;

/**
 * DataProvider modifier on add to subscription form for bundle products.
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
                                    'additionalClasses' => 'product-options bundle-options',
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
     * Return bundle selections meta data.
     *
     * @return array
     */
    private function getAttributesMeta()
    {
        $result = [];
        $iterator = 0;
        $bundleOptions = $this->getBundleOptions();
        $selectedBundleOptions = $this->getSelectedBundleOptions();

        foreach ($bundleOptions as $bundleOption) {
            if (!isset($selectedBundleOptions[$bundleOption->getId()])) {
                continue;
            }
            $selections  = $bundleOption->getSelections();
            $selectionData = [
                'attributeLabel' => $bundleOption->getTitle()
            ];
            $label = '';
            if (is_array($selectedBundleOptions[$bundleOption->getId()])) {
                foreach ($selections as $selection) {
                    if (in_array($selection->getSelectionId(), $selectedBundleOptions[$bundleOption->getId()])) {
                        $label .= (float)$selection->getSelectionQty() . ' x ' . $selection->getName() . '<br>';
                    }
                }
            } else {
                foreach ($selections as $selection) {
                    $label = $selectedBundleOptions['qty'][$bundleOption->getId()] . ' x ' . $selection->getName();
                }
            }
            $selectionData['optionLabel'] = $label;

            $iterator++;
                $result[self::CONTAINER_PREFIX . $bundleOption->getId()] = [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => $selectionData['attributeLabel'],
                                'collapsible' => false,
                                'componentType' => Field::NAME,
                                'formElement' => Input::NAME,
                                'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                                'sortOrder' => $iterator,
                                'value' => $selectionData['optionLabel'],
                            ],
                        ],
                    ],
                ];
        }
        return $result;
    }

    /**
     * Get all bundle options
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
     * Get selected bundle options
     * @return mixed
     */
    public function getSelectedBundleOptions()
    {
        return $this->formContext->getRequest()->getParam(self::CONTAINER_PREFIX);
    }
}
