<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Edit\Modifier\EditProduct;

use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Framework\Registry;
use Magento\Framework\UrlFactory;
use TNW\Subscriptions\Model\Context;

/**
 * DataProvider modifier on edit subscription form for attribute products.
 */
class Attributes extends Base
{
    /**
     * Options container prefix
     */
    const CONTAINER_PREFIX = 'additional_attribute';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Context
     */
    private $contextModel;

    /**
     * @param Registry $registry
     * @param UrlFactory $urlFactory
     * @param Context $contextModel
     */
    public function __construct(
        Registry $registry,
        UrlFactory $urlFactory,
        Context $contextModel
    ) {
        parent::__construct($urlFactory);
        $this->registry = $registry;
        $this->contextModel = $contextModel;
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
                                        'middle_container' => $this->getProductAttributesMeta(),
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
     * {@inheritdoc}
     */
    protected function isUsedModifier()
    {
        return false;
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
                                'additionalClasses' => 'edit-options-button action-advanced action-additional',
                                'additionalForGroup' => true,
                                'displayAsLink' => true,
                                'title' => '[' . __('Edit attributes') . ']',
                                'actions' => [
                                    [
                                        'targetName' => $leftContainerName . '.edit_attributes',
                                        'actionName' => 'editAttributes',
                                        'params' =>  [
                                            $this->getProduct()->getId(), //product id
                                            $this->getItem()->getId(),  //subscription item id
                                            $this->getItem()->getSubscriptionProfileId(),  // subscription id
                                            //$this->getItem()->getCustomOptions() //super attributes data
                                        ],
                                    ],
                                ],
                                'configureUrl' => $this->getConfigureUrl(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Return configurable product attributes meta data.
     *
     * @return array
     */
    private function getProductAttributesMeta()
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

        $attributesData = [[
            'attributeId' => 'test',
            'attributeLabel' => 'Test',
            'optionLabel' => 'test',
        ]];

        foreach ($attributesData as $attributeData) {
            $iterator++;
            $result[self::CONTAINER_PREFIX . $attributeData['attributeId']] = [
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
}