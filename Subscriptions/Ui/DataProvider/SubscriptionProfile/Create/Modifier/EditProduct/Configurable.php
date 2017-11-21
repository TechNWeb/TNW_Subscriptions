<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\EditProduct;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Framework\Registry;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;

/**
 * DataProvider modifier on add to subscription form for configurable products.
 */
class Configurable extends Base
{
    /**
     * Current product type
     */
    const PRODUCT_TYPE = ConfigurableProduct::TYPE_CODE;

    /**
     * Options container prefix
     */
    const CONTAINER_PREFIX = 'super_attribute';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
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
                    'children' => [
                        'form' => [
                            'children' => [
                                'description_fieldset' => [
                                    'children' => [
                                        'left_container' => $this->editOptionsButtonMeta(),
                                        'middle_container' => $this->getProductOptionsMeta(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        return $meta;
    }

    /**
     * Return configurable product options meta data.
     *
     * @return array
     */
    private function getProductOptionsMeta()
    {
        return [
            'children' => [
                'options' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => Fieldset::NAME,
                                'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                                'sortOrder' => 100,
                                'dataScope' => self::CONTAINER_PREFIX,
                                'additionalClasses' => 'product-options',
                            ],
                        ],
                    ],
                    'children' => $this->getAttributesMeta(),
                ],
            ],
        ];
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
                            'label' => $attributeData['attributeLabel'] . ':',
                            'collapsible' => false,
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'additionalClasses' => 'edit-product',
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
     * Return 'Edit options' button meta data.
     *
     * @return array
     */
    private function editOptionsButtonMeta()
    {
        $currentFormName = $this->registry->registry('form_full_name');
        $leftContainerName = $currentFormName . '.description_fieldset.left_container';

        return [
            'children' => [
                'edit_options' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => UiContainer::NAME,
                                'componentType' => UiContainer::NAME,
                                'component' => 'TNW_Subscriptions/js/components/options-button',
                                'additionalClasses' => 'edit-options-button action-advanced',
                                'additionalForGroup' => true,
                                'displayAsLink' => true,
                                'title' => '[' . __('Edit options') . ']',
                                'actions' => [
                                    [
                                        'targetName' => $leftContainerName . '.edit_options',
                                        'actionName' => 'editOptions',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
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
        return $this->getItem()->getBuyRequest()->getSuperAttribute();
    }
}
