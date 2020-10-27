<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Edit\Modifier\EditProduct;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlFactory;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerConfigurable;

/**
 * DataProvider modifier on edit subscription form for configurable products.
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
     * @var ManagerConfigurable
     */
    private $managerConfigurable;

    /**
     * @var Context
     */
    private $contextModel;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Configurable constructor.
     * @param Registry $registry
     * @param UrlFactory $urlFactory
     * @param ManagerConfigurable $managerConfigurable
     * @param Context $contextModel
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Registry $registry,
        UrlFactory $urlFactory,
        ManagerConfigurable $managerConfigurable,
        Context $contextModel,
        SerializerInterface $serializer
    ) {
        $this->registry = $registry;
        $this->managerConfigurable = $managerConfigurable;
        $this->contextModel = $contextModel;
        $this->serializer = $serializer;
        parent::__construct($urlFactory);
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

        $attributesData = $this->managerConfigurable->getConfigurableOptionsData($this->getItem());

        foreach ($attributesData as $attributeData) {
            $iterator++;
            $result[] = [
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

        $customOptions = $this->getItem()->getCustomOptions();

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
                                'title' => '[' . __('Edit options') . ']',
                                'actions' => [
                                    [
                                        'targetName' => $leftContainerName . '.edit_options',
                                        'actionName' => 'editOptions',
                                        'params' =>  [
                                            $this->getProduct()->getId(), //product id
                                            $this->getItem()->getId(),  //subscription item id
                                            $this->getItem()->getSubscriptionProfileId(),  // subscription id
                                            isset($customOptions['info_buyRequest']['super_attribute'])
                                                ? $this->serializer->serialize($customOptions['info_buyRequest']['super_attribute'])
                                                : '', //super attributes data
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
}
