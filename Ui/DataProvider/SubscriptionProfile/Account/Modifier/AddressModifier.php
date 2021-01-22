<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Account\Modifier;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\Address\Mapper as AddressMapper;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Customer\Model\Customer\Mapper as CustomerMapper;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\ResourceModel\Form\Attribute\Collection;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\Json\Encoder;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Address form modifier.
 */
class AddressModifier implements ModifierInterface
{
    const FORM_CODE = 'adminhtml_customer_address';

    /**
     * Attributes that will not be shown.
     */
    private $skippedAttributes = [
        'region',
        'prefix',
        'middlename',
        'suffix'
    ];

    /**
     * Frontend inputs and form elements mapping.
     *
     * @var array
     */
    private $formElementsMapping = [
        'text' => 'input',
        'multiline' => 'input',
        'select' => 'select',
    ];

    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * Address attributes collection.
     *
     * @var Collection
     */
    private $addressAttributes;

    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * Converts Address Service Data Object to an array.
     *
     * @var AddressMapper
     */
    private $addressMapper;

    /**
     * Converts Customer Object to an array.
     *
     * @var CustomerMapper
     */
    private $customerMapper;

    /**
     * @var Encoder
     */
    private $jsonEncoder;

    /**
     * @var ArrayManager
     */
    private ArrayManager $arrayManager;

    /**
     * @var string
     */
    private $fieldsetPath;

    /**
     * @var string
     */
    private $dataScope;

    /**
     * @var bool
     */
    private $labelsVisible;

    /**
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param CustomerRepository $customerRepository
     * @param AddressRepository $addressRepository
     * @param AddressMapper $addressMapper
     * @param CustomerMapper $customerMapper
     * @param QuoteSessionInterface $session
     * @param Encoder $encoder
     * @param ArrayManager $arrayManager
     * @param string $fieldsetPath
     * @param string $dataScope
     * @param bool $labelsVisible
     */
    public function __construct(
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        CustomerRepository $customerRepository,
        AddressRepository $addressRepository,
        AddressMapper $addressMapper,
        CustomerMapper $customerMapper,
        QuoteSessionInterface $session,
        Encoder $encoder,
        ArrayManager $arrayManager,
        $fieldsetPath,
        $dataScope,
        $labelsVisible
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->addressMapper = $addressMapper;
        $this->customerMapper = $customerMapper;
        $this->session = $session;
        $this->jsonEncoder = $encoder;
        $this->arrayManager = $arrayManager;
        $this->fieldsetPath = $fieldsetPath;
        $this->dataScope = $dataScope;
        $this->labelsVisible = $labelsVisible;
    }

    public function modifyData(array $data)
    {
        return $data;
        // TODO: Implement modifyData() method.
    }

    public function modifyMeta(array $meta)
    {
        $meta = $this->arrayManager->populate($this->fieldsetPath, $meta);

        $result = $this->arrayManager->merge(
            $this->fieldsetPath,
            $meta,
            [
                'children' => $this->getAddressFieldsMeta()
            ]
        );
        return $result;
    }

    private function getAddressFieldsMeta()
    {
        $attributes = $this->getAddressAttributes();
        /** @var array $childrenData */
        $childrenData = [];
        $sortOrder = 2;
        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            if (!in_array($attribute->getAttributeCode(), $this->skippedAttributes)) {
                $lineCount = $attribute->getMultilineCount();
                $i = 0;
                //The cycle here is for multiline attributes (to show all necessary lines on the form)
                do {
                    $sortOrder++;
                    //Get meta data for attribute
                    $childrenData = array_merge_recursive(
                        $childrenData,
                        $this->getAttributeMeta($attribute, $sortOrder, $i)
                    );
                    $i++;
                    $lineCount--;
                } while ($lineCount > 0);
            }
        }

        return $childrenData;
    }

    /**
     * Get attribute meta data for UI component.
     *
     * @param Attribute $attribute
     * @param int $sortOrder
     * @param int $attributeLine
     * @return array
     */
    private function getAttributeMeta(Attribute $attribute, $sortOrder, $attributeLine)
    {
        $attributeCode = $attribute->getAttributeCode();
        $result = [];
        $attributeMeta = [];
        $additionalClasses = $this->getAdditionalClasses($attribute);
        $formElement = $this->getFormElement($attribute);
        list($elemName, $elemLabel) = $this->getElemNameAndLabel($attribute, $attributeLine);
        list($attributeMeta, $additionalClasses) = $this->getSourceAttributeMeta(
            $attribute,
            $attributeMeta,
            $elemLabel,
            $additionalClasses
        );

        //Meta data for all attributes.
        $fieldConfig = [
            'config' => [
                'componentType'     => 'field',
                'placeholder'       => __($elemLabel),
                'additionalClasses' => $additionalClasses,
                'validation'        => $this->getValidation($attribute, $attributeLine),
                'sortOrder'         => $sortOrder,
                'dataScope'         => $this->dataScope . '.' . $elemName,
                'imports'           => [
                    'disabled' => '!${ $.parentName }:visible',
                    '__disableTmpl' => [
                        'disabled' => false
                    ]
                ],
                'label'             => $this->labelsVisible ? __($elemLabel) : ''
            ],
        ];

        //Additional meta data for attributes.
        $attributeMeta = $this->getPostCodeAttributeMeta($attributeCode, $attributeMeta);

        $attributeMeta = array_replace_recursive(
            $attributeMeta,
            $fieldConfig
        );

        if ($attribute->getAttributeCode() == 'region_id') {
            $attributeMeta = $this->getRegionIdAttributeMeta($attributeMeta);
        } else {
            $elementFormData = [
                'config' => [
                    'formElement' => $formElement,
                    'dataType' => 'text',

                ]
            ];

            $attributeMeta = array_merge_recursive($attributeMeta, $elementFormData);
        }

        $result[$elemName]['arguments']['data'] = $attributeMeta;

        return $result;
    }

    /**
     * Returns additional meta data for post_code attribute.
     *
     * @param string $attributeCode
     * @param array $attributeMeta
     * @return array
     */
    private function getPostCodeAttributeMeta($attributeCode, array $attributeMeta)
    {
        if ($attributeCode == 'postcode') {
            $attributeMeta = array_merge_recursive(
                $attributeMeta,
                [
                    'config' => [
                        'component' => 'Magento_Ui/js/form/element/post-code',
                        'validation' => [
                            'required-entry' => true,
                        ]
                    ],
                ]
            );
        }

        return $attributeMeta;
    }

    /**
     * Returns additional classes for attribute from frontend model.
     *
     * @param Attribute $attribute
     * @return string
     */
    private function getAdditionalClasses($attribute)
    {
        return $attribute->getFrontend()->getClass();
    }

    /**
     * Retrieve type of form element for attribute from attribute frontendInput.
     *
     * @param Attribute $attribute
     * @return string
     */
    private function getFormElement(Attribute $attribute)
    {
        $formElement = $attribute->getFrontendInput();

        if (isset($this->formElementsMapping[$formElement])) {
            $formElement = $this->formElementsMapping[$formElement];
        }

        return $formElement;
    }

    /**
     * Returns displayed on form name and label.
     *
     * @param Attribute $attribute
     * @param int $attributeLine
     * @return array
     */
    private function getElemNameAndLabel(Attribute $attribute, $attributeLine)
    {
        $attributeCode = $attribute->getAttributeCode();
        $elemName = $attributeCode;
        $elemLabel = $attribute->getStoreLabel();

        //If there is multiline attribute we have to show all lines on the form.
        if ($attribute->getFrontendInput() == 'multiline') {
            $elemName = $attributeCode . $attributeLine;
            $elemLabel = $attribute->getStoreLabel() . ' ' . sprintf(__('(Line %s)'), $attributeLine + 1);
        }

        return [$elemName, $elemLabel];
    }

    /**
     * Returns additional meta data for select/multiselect attributes.
     *
     * @param Attribute $attribute
     * @param array     $attributeMeta
     * @param string    $elemLabel
     * @param string    $additionalClasses
     * @return array
     */
    private function getSourceAttributeMeta(
        Attribute $attribute,
        array $attributeMeta,
        $elemLabel,
        $additionalClasses
    ) {
        if ($attribute->usesSource()) {
            $attributeMeta = array_merge_recursive(
                $attributeMeta,
                [
                    'options' => $attribute->getSource(),
                    'config'  => [
                        'caption' => __($elemLabel),
                    ]
                ]
            );
            $additionalClasses = $additionalClasses . ' wide-select';
        }

        return [$attributeMeta, $additionalClasses];
    }

    /**
     * Get attribute validation rules.
     *
     * @param Attribute $attribute
     * @param int $attributeLine
     * @return array
     */
    private function getValidation(Attribute $attribute, $attributeLine)
    {
        $validation = [];
        //Form validation rules array
        if ($attribute->getValidateRules()) {
            foreach ($attribute->getValidateRules() as $name => $value) {
                $validation[$name] = $value;
            }
        }
        //For multiline attribute required entry validation must be shown only on the first line
        if ($attributeLine == 0 && $attribute->getAttributeCode() != 'region_id') {
            $validation['required-entry'] = (bool)$attribute->getIsRequired();
        }

        return $validation;
    }

    /**
     * Returns additional meta data for region_id attribute.
     *
     * @param array $attributeMeta
     * @return array
     */
    private function getRegionIdAttributeMeta(array $attributeMeta)
    {
        $attributeMeta = array_merge_recursive(
            $attributeMeta,
            [
                'config' => [
                    'elementTmpl' => 'ui/form/element/select',
                    'customEntry' => 'region',
                    'component' => 'Magento_Ui/js/form/element/region',
                    'formElement' => 'select',
                    'filterBy' => [
                        'target' => '${ $.provider }:${ $.parentScope }.country_id',
                        'field' => 'country_id',
                        '__disableTmpl' => [
                            'target' => false
                        ]
                    ],
                    'validation' => [
                        'required-entry' => true,
                    ],
                    'customScope' => 'region'
                ],
            ]
        );

        return $attributeMeta;
    }

    /**
     * Returns customer address form attributes collection.
     *
     * @return Collection
     */
    private function getAddressAttributes()
    {
        if (null === $this->addressAttributes) {
            $this->addressAttributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                self::FORM_CODE
            );
        }
        return $this->addressAttributes;
    }
}
