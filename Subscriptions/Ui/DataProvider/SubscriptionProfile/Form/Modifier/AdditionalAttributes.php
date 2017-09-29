<?php
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Ui\Component\Form;
use Magento\Ui\DataProvider\Mapper\FormElement as FormElementMapper;
use Magento\Eav\Api\Data\AttributeGroupInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;

/**
 * Prepare eav attribute ui layout.
 */
class AdditionalAttributes extends BaseFormModifier
{

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var AttributeGroupInterface[]
     */
    private $attributeGroups;

    /**
     * @var AttributeGroupRepositoryInterface
     */
    private $attributeGroupRepository;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var FormElementMapper
     */
    private $formElementMapper;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute[]
     */
    private $attributes;

    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        AttributeRepositoryInterface $attributeRepository,
        ArrayManager $arrayManager,
        FormElementMapper $formElementMapper
    ) {
        parent::__construct($urlBuilder, $registry);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->attributeRepository = $attributeRepository;
        $this->arrayManager = $arrayManager;
        $this->formElementMapper = $formElementMapper;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        foreach ($this->getGroups() as $groupCode => $group) {
            if (strcasecmp($groupCode, 'additional-information') !== 0) {
                continue;
            }

            $attributes = !empty($this->getAttributes()[$groupCode]) ? $this->getAttributes()[$groupCode] : [];

            $meta['additional_attributes'] = [
                'children' => $this->getAttributesMeta($attributes, $group),
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => false,
                            'collapsible' => false,
                            'opened' => false,
                            'componentType' => Form\Fieldset::NAME,
                            'sortOrder' => 10
                        ],
                    ],
                ],
            ];
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $profileId = $this->getProfile()->getId();

        /** @var string $groupCode */
        foreach (array_keys($this->getGroups()) as $groupCode) {
            /** @var \Magento\Eav\Model\Entity\Attribute[] $attributes */
            $attributes = !empty($this->getAttributes()[$groupCode]) ? $this->getAttributes()[$groupCode] : [];

            foreach ($attributes as $attribute) {
                if (null === ($attributeValue = $this->setupAttributeData($attribute))) {
                    continue;
                }

                $data[$profileId]['additional_attributes'][$attribute->getAttributeCode()] = $attributeValue;
            }
        }

        $data[$profileId]['additional_attributes']['subscription_profile_id'] = $profileId;
        return $data;
    }

    /**
     * Setup attribute data
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @return mixed|null
     * @api
     */
    public function setupAttributeData($attribute)
    {
        return $this->getProfile()
            ->getData($attribute->getAttributeCode());
    }

    /**
     * Get attributes meta
     *
     * @param \Magento\Eav\Model\Entity\Attribute[] $attributes
     * @param string $groupCode
     * @return array
     */
    public function getAttributesMeta($attributes, $groupCode)
    {
        $meta = [];

        foreach ($attributes as $sortOrder => $attribute) {
            $meta[$attribute->getAttributeCode()] = $this->setupAttributeMeta($attribute, $groupCode, $sortOrder);
        }

        return $meta;
    }

    /**
     * Initial meta setup
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param string $groupCode
     * @param int $sortOrder
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @api
     */
    public function setupAttributeMeta($attribute, $groupCode, $sortOrder)
    {
        $meta = $this->arrayManager->set('arguments/data/config', [], [
            'dataType' => $attribute->getFrontendInput(),
            'formElement' => $this->getFormElementsMapValue($attribute->getFrontendInput()),
            'required' => $attribute->getIsRequired(),
            'notice' => $attribute->getNote(),
            'default' => $attribute->getDefaultValue(),
            'label' => $attribute->getDefaultFrontendLabel(),
            'code' => $attribute->getAttributeCode(),
            'source' => $groupCode,
            'globalScope' => true,
            'sortOrder' => $sortOrder,
            'componentType' => Form\Field::NAME,
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

        return $meta;
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
     * Retrieve groups
     *
     * @return AttributeGroupInterface[]
     */
    private function getGroups()
    {
        if (empty($this->attributeGroups)) {
            $searchCriteria = $this->prepareGroupSearchCriteria()->create();
            $attributeGroupSearchResult = $this->attributeGroupRepository->getList($searchCriteria);
            foreach ($attributeGroupSearchResult->getItems() as $group) {
                $this->attributeGroups[$group->getAttributeGroupCode()] = $group;
            }
        }

        return $this->attributeGroups;
    }

    /**
     * Initialize attribute group search criteria with filters.
     *
     * @return SearchCriteriaBuilder
     */
    private function prepareGroupSearchCriteria()
    {
        return $this->searchCriteriaBuilder->addFilter(
            AttributeGroupInterface::ATTRIBUTE_SET_ID,
            $this->getAttributeSetId()
        );
    }

    /**
     * Return current attribute set id
     *
     * @return int|null
     */
    private function getAttributeSetId()
    {
        /** @var \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile $resource */
        $resource = $this->getProfile()->getResource();
        return $resource->getEntityType()->getDefaultAttributeSetId();
    }

    /**
     * Retrieve attributes
     *
     * @return \Magento\Eav\Model\Entity\Attribute[]
     */
    private function getAttributes()
    {
        if (!$this->attributes) {
            foreach ($this->getGroups() as $group) {
                $this->attributes[$group->getAttributeGroupCode()] = $this->loadAttributes($group);
            }
        }

        return $this->attributes;
    }

    /**
     * Loading product attributes from group
     *
     * @param AttributeGroupInterface $group
     * @return AttributeInterface[]
     */
    private function loadAttributes(AttributeGroupInterface $group)
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('sort_order')
            ->setAscendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(AttributeGroupInterface::GROUP_ID, $group->getAttributeGroupId())
            ->addSortOrder($sortOrder)
            ->create();

        return $this->attributeRepository
            ->getList(\TNW\Subscriptions\Model\SubscriptionProfile::ENTITY, $searchCriteria)
            ->getItems();
    }
}