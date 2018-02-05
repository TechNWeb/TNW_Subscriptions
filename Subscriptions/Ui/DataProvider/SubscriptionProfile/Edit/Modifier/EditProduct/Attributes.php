<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
use Magento\Framework\Registry;
use Magento\Framework\UrlFactory;
use Magento\Ui\DataProvider\Mapper\FormElement;
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
     * @var FormElement
     */
    private $formElementMapper;

    /**
     * @param Registry $registry
     * @param UrlFactory $urlFactory
     * @param Context $contextModel
     * @param ArrayManager $arrayManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepositoryInterface $attributeRepository
     * @param AttributeGroupRepositoryInterface $attributeGroupRepository
     * @param EavConfig $eavConfig
     * @param FormElement $formElementMapper
     */
    public function __construct(
        Registry $registry,
        UrlFactory $urlFactory,
        Context $contextModel,
        ArrayManager $arrayManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        EavConfig $eavConfig,
        FormElement $formElementMapper
    ) {
        parent::__construct($urlFactory);
        $this->registry = $registry;
        $this->contextModel = $contextModel;
        $this->arrayManager = $arrayManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->eavConfig = $eavConfig;
        $this->formElementMapper = $formElementMapper;
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
                //'source' => $groupCode,
                'globalScope' => true,
                'sortOrder' => $iterator,
                'componentType' => Form\Field::NAME,
                'previewElementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                'showPreview' => false,
            ]);

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

            $result[self::CONTAINER_PREFIX . $attribute->getAttributeId()] = $meta;
        }

        return $result;
    }

    /**
     * Retrieve form element
     *
     * @param string $value
     * @return mixed
     */
    private function getFormElementsMapValue($value)
    {
        $valueMap = $this->formElementMapper->getMappings();
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

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(AttributeGroupInterface::GROUP_ID, reset($attributeGroupSearchResult)->getAttributeGroupId())
            ->create();

        return $this->attributeRepository
            ->getList(ProductSubscriptionProfile::ENTITY, $searchCriteria)
            ->getItems();
    }
}
