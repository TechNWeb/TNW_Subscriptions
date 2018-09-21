<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Edit\Modifier\EditProduct;

use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeGroupInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Framework\Registry;
use Magento\Framework\UrlFactory;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileAttributeInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;

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
     * @var AttributeGroupRepositoryInterface
     */
    private $attributeGroupRepository;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var array
     */
    private $loadAttributes;

    /**
     * @var bool
     */
    private $editAllow;

    /**
     * @param UrlFactory $urlFactory
     * @param Registry $registry
     * @param Context $contextModel
     * @param ArrayManager $arrayManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepositoryInterface $attributeRepository
     * @param AttributeGroupRepositoryInterface $attributeGroupRepository
     * @param EavConfig $eavConfig
     * @param bool $editAllow
     */
    public function __construct(
        UrlFactory $urlFactory,
        Registry $registry,
        Context $contextModel,
        ArrayManager $arrayManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        EavConfig $eavConfig,
        $editAllow = true
    ) {
        parent::__construct($urlFactory);
        $this->registry = $registry;
        $this->contextModel = $contextModel;
        $this->arrayManager = $arrayManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->eavConfig = $eavConfig;
        $this->editAllow = $editAllow;
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
        foreach ($this->loadAttributes() as $loadAttribute) {
            $data['additional_attribute'][$loadAttribute->getAttributeCode()]
                = $this->getItem()->getData($loadAttribute->getAttributeCode());
        }

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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProductAttributesMeta()
    {
        return [
            'children' => [
                'edit_attributes' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => UiContainer::NAME,
                                'componentType' => UiContainer::NAME,
                                'component' => 'TNW_Subscriptions/js/components/edit-button',
                                'additionalClasses' => 'edit-attributes-button action-advanced action-additional',
                                'additionalForGroup' => true,
                                'displayAsLink' => true,
                                'title' => __('show custom attributes'),
                                'activeTitle' => __('hide custom attributes'),
                                'sortOrder' => 119,
                                'actions' => [
                                    [
                                        'targetName' => '${ $.name }',
                                        'actionName' => 'toggle',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'attributes' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => Form\Fieldset::NAME,
                                'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                                'sortOrder' => 120,
                                'dataScope' => self::CONTAINER_PREFIX,
                                'additionalClasses' => 'product-attributes',
                                'imports' => [
                                    'visible' => '${ $.parentName }.edit_attributes:active',
                                ],
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAttributesMeta()
    {
        $result = [];
        $iterator = 0;

        foreach ($this->loadAttributes() as $attribute) {
            $iterator++;

            $meta = $this->arrayManager->set('arguments/data/config', [], [
                'dataType' => $attribute->getFrontendInput(),
                'formElement' => $this->getFormElementsMapValue($attribute->getFrontendInput()),
                'required' => $attribute->getIsRequired(),
                'notice' => $attribute->getNote(),
                'default' => $attribute->getDefaultValue(),
                'label' => $attribute->getDefaultFrontendLabel(),
                'code' => $attribute->getAttributeCode(),
                'globalScope' => true,
                'sortOrder' => $iterator,
                'additionalClasses' => 'edit-product',
                'componentType' => Form\Field::NAME,
                'previewElementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
            ]);

            if ($this->editAllow) {
                $meta = $this->arrayManager->merge('arguments/data/config', $meta, [
                    'imports' => [
                        'showPreview' => "{$this->registry->registry('form_full_name')}:previewMode",
                    ],
                ]);
            }

            if ($attribute->usesSource()) {
                $meta = $this->arrayManager->merge('arguments/data/config', $meta, [
                    'options' => $attribute->getSource()->getAllOptions(),
                ]);
            }

            if ($attribute->getFrontendInput() === 'boolean') {
                $meta['arguments']['data']['config']['prefer'] = 'toggle';
                $meta['arguments']['data']['config']['valueMap'] = [
                    'true' => '1',
                    'false' => '0',
                ];
            }

            $result[$attribute->getAttributeCode()] = $meta;
        }

        return $result;
    }

    /**
     * Retrieve form element
     *
     * @param string $value
     * @return string
     */
    private function getFormElementsMapValue($value)
    {
        static $valueMap = [
            'text' => 'input',
            'hidden' => 'input',
            'boolean' => 'checkbox',
            'media_image' => 'image',
            'price' => 'input',
            'weight' => 'input',
            'gallery' => 'image',
        ];

        return isset($valueMap[$value]) ? $valueMap[$value] : $value;
    }

    /**
     * Loading product attributes
     *
     * @return \Magento\Eav\Api\Data\AttributeInterface[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loadAttributes()
    {
        if (empty($this->loadAttributes)) {
            $attributeSetId = $this->eavConfig
                ->getEntityType(ProductSubscriptionProfile::ENTITY)
                ->getDefaultAttributeSetId();

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(AttributeGroupInterface::ATTRIBUTE_SET_ID, $attributeSetId)
                ->addFilter(AttributeGroupInterface::GROUP_NAME, 'Additional information')
                ->create();

            $attributeGroupSearchResult = $this->attributeGroupRepository
                ->getList($searchCriteria)
                ->getItems();

            if (empty($attributeGroupSearchResult)) {
                return [];
            }

            $this->searchCriteriaBuilder
                ->addFilter(
                    AttributeGroupInterface::GROUP_ID,
                    reset($attributeGroupSearchResult)->getAttributeGroupId()
                );

            if (!$this->editAllow) {
                $this->searchCriteriaBuilder
                    ->addFilter(
                        ProductSubscriptionProfileAttributeInterface::IS_VISIBLE_ON_FRONT,
                        1
                    );
            }

            $searchCriteria = $this->searchCriteriaBuilder->create();
            $this->loadAttributes = $this->attributeRepository
                ->getList(ProductSubscriptionProfile::ENTITY, $searchCriteria)
                ->getItems();
        }

        return $this->loadAttributes;
    }
}
