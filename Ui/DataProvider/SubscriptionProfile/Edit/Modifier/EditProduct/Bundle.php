<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Edit\Modifier\EditProduct;

use Magento\Bundle\Model\Product\Type as TypeBundle;
use Magento\Framework\UrlFactory;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerBundle;

/**
 * DataProvider modifier on edit subscription form for bundle products.
 */
class Bundle extends Base
{
    /**
     * Current product type
     */
    const PRODUCT_TYPE = TypeBundle::TYPE_CODE;

    /**
     * Options container prefix
     */
    const CONTAINER_PREFIX = 'bundle_options';

    /**
     * @var ManagerBundle
     */
    private $managerBundle;

    /**
     * Configurable constructor.
     * @param UrlFactory $urlFactory
     * @param ManagerBundle $managerBundle
     */
    public function __construct(
        UrlFactory $urlFactory,
        ManagerBundle $managerBundle
    ) {
        parent::__construct($urlFactory);
        $this->managerBundle = $managerBundle;
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
     * Return bundle product options meta data.
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
     * Return bundle options meta data.
     *
     * @return array
     */
    private function getAttributesMeta()
    {
        $result = [];
        $iterator = 0;

        $attributesData = $this->managerBundle->getBundleOptionsData($this->getItem());

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
}
