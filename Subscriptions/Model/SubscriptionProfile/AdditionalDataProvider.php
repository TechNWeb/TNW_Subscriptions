<?php
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Ui\Component\Form;

class AdditionalDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @var \Magento\Ui\DataProvider\Mapper\FormElement
     */
    private $formElementMapper;

    /**
     * @var \Magento\Eav\Api\AttributeGroupRepositoryInterface
     */
    private $attributeGroupRepository;

    /**
     * @var \Magento\Eav\Api\Data\AttributeGroupInterface[]
     */
    private $attributeGroups;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute[]
     */
    private $attributes;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $attributeRepository;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\CollectionFactory $collectionFactory,
        \Magento\Eav\Api\AttributeGroupRepositoryInterface $attributeGroupRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository,
        \Magento\Ui\DataProvider\Mapper\FormElement $formElementMapper,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Framework\Stdlib\ArrayManager $arrayManager,
        \Magento\Eav\Model\Config $eavConfig,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->collection = $collectionFactory->create()
            ->addAttributeToSelect('*');
        $this->formElementMapper = $formElementMapper;
        $this->arrayManager = $arrayManager;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        foreach ($this->getGroups() as $groupCode => $group) {
            if (strcasecmp($groupCode, 'additional-information') !== 0) {
                continue;
            }

            $attributes = !empty($this->getAttributes()[$groupCode]) ? $this->getAttributes()[$groupCode] : [];
            $meta['additional']['children'] = $this->getAttributesMeta($attributes, $group);
        }

        return $meta;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        return $data;
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
     * @return \Magento\Eav\Api\Data\AttributeGroupInterface[]
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
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private function prepareGroupSearchCriteria()
    {
        return $this->searchCriteriaBuilder->addFilter(
            \Magento\Eav\Api\Data\AttributeGroupInterface::ATTRIBUTE_SET_ID,
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
        return $this->eavConfig
            ->getEntityType(\TNW\Subscriptions\Model\SubscriptionProfile::ENTITY)
            ->getDefaultAttributeSetId();
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
     * @param \Magento\Eav\Api\Data\AttributeGroupInterface $group
     * @return \Magento\Eav\Api\Data\AttributeInterface[]
     */
    private function loadAttributes($group)
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('sort_order')
            ->setAscendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\Eav\Api\Data\AttributeGroupInterface::GROUP_ID, $group->getAttributeGroupId())
            ->addSortOrder($sortOrder)
            ->create();

        return $this->attributeRepository
            ->getList(\TNW\Subscriptions\Model\SubscriptionProfile::ENTITY, $searchCriteria)
            ->getItems();
    }
}