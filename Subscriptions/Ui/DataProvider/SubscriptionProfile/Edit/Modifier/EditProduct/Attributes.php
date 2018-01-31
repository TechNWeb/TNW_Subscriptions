<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Edit\Modifier\EditProduct;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form;
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
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param Registry $registry
     * @param UrlFactory $urlFactory
     * @param Context $contextModel
     * @param ArrayManager $arrayManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        Registry $registry,
        UrlFactory $urlFactory,
        Context $contextModel,
        ArrayManager $arrayManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository
    ) {
        parent::__construct($urlFactory);
        $this->registry = $registry;
        $this->contextModel = $contextModel;
        $this->arrayManager = $arrayManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->isUsedModifier()) {
            return $meta;
        }

        return $this->arrayManager->merge('children/form/children/description_fieldset/children', $meta, [
            'middle_container' => $this->getProductAttributesMeta()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        $this->getItem();
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function isUsedModifier()
    {
        return true;
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
                'attributes' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => Form\Fieldset::NAME,
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
                            'componentType' => Form\Field::NAME,
                            'formElement' => Form\Element\Input::NAME,
                            'additionalClasses' => 'edit-product',
                            'previewElementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                            'sortOrder' => $iterator,
                            'value' => $attributeData['optionLabel'],
                            'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                            'showPreview' => false,
                        ],
                    ],
                ],
            ];
        }

        return $result;
    }

    /**
     * Loading product attributes
     *
     * @return \Magento\Eav\Api\Data\AttributeInterface[]
     */
    private function loadAttributes()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\Eav\Api\Data\AttributeGroupInterface::GROUP_ID, '')
            ->create();

        return $this->attributeRepository
            ->getList(\TNW\Subscriptions\Model\ProductSubscriptionProfile::ENTITY, $searchCriteria)
            ->getItems();
    }
}