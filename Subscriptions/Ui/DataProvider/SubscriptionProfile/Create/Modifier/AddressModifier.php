<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Ui\Component\Form;
use Magento\Customer\Model\Address\Mapper as AddressMapper;
use Magento\Customer\Model\Customer\Mapper as CustomerMapper;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Account;

class AddressModifier implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**#@+
     * FieldSets dataScope
     */
    const SHIPPING_INFO_FIELDSET = 'shipping_info';
    const BILLING_INFO_FIELDSET = 'billing_info';
    const SHIPPING_ADDRESS_FIELDSET = 'shipping_address';
    const BILLING_ADDRESS_FIELDSET = 'billing_address';
    const FORM_CODE = 'adminhtml_customer_address';
    /**#@-*/

    /**#@+
     * FieldSets Names
     */
    const INFO_FIELDSET_NAME = 'info_fields';
    const ADDRESS_FIELDSET_NAME = 'address_fields';
    /**#@-*/

    /**
     * Select form component with customer addresses list.
     */
    const CUSTOMER_ADDRESS_SELECT = 'customer_address_id';

    /**
     * Information fieldSet attributes.
     */
    private $infoAttributes = [
        'firstname',
        'lastname',
        'company',
        'telephone'
    ];

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
    private $formElementsmapping = [
        'text' => 'input',
        'multiline' => 'input',
        'select' => 'select',
    ];

    /**
     * Flag that indicates that this is a shipping address form.
     *
     * @var bool
     */
    private $isShipping;

    /**
     * Information fieldset name.
     *
     * @var string
     */
    private $infoFieldSetName;

    /**
     * Address fieldset name.
     *
     * @var string
     */
    private $addressDataFieldSetName;

    /**
     * Customer address Id.
     *
     * @var bool|integer
     */
    private $addressId = false;

    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * Address attributes collection.
     *
     * @var \Magento\Customer\Model\ResourceModel\Form\Attribute\Collection
     */
    private $addressAttributes;

    /** @var QuoteSessionInterface */
    private $session;

    /** @var CustomerRepository */
    private $customerRepository;

    /** @var AddressRepository */
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
     * AddressModifier constructor.
     *
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param CustomerRepository $customerRepository
     * @param AddressRepository $addressRepository
     * @param AddressMapper $addressMapper
     * @param CustomerMapper $customerMapper
     * @param QuoteSessionInterface $session
     */
    public function __construct(
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        CustomerRepository $customerRepository,
        AddressRepository $addressRepository,
        AddressMapper $addressMapper,
        CustomerMapper $customerMapper,
        QuoteSessionInterface $session,
        $isShipping
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->addressMapper = $addressMapper;
        $this->customerMapper = $customerMapper;
        $this->session = $session;
        $this->isShipping = $isShipping;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $fieldSetsChildren = $this->getFieldSetsChildren();

        $meta = array_merge_recursive(
            $meta,
            [
                static::INFO_FIELDSET_NAME => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => $this->getInfoFieldSetLabel(),
                                'collapsible' => false,
                                'componentType' => Form\Fieldset::NAME,
                                'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                                'sortOrder' => 20,
                                'dataScope' => $this->getInfoFieldSetDataScope(),
                            ],
                        ],
                    ],
                    'children' => $fieldSetsChildren[static::INFO_FIELDSET_NAME],
                ],
                static::ADDRESS_FIELDSET_NAME => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'dataScope' => $this->getAddressDataFieldSetdataScope()
                            ],
                        ],
                    ],
                    'children' => array_merge(
                        $this->getButtonsSet(),
                        $fieldSetsChildren[static::ADDRESS_FIELDSET_NAME],
                        $this->getAddressIdMeta()
                    )
                ],
            ]
        );

        return $meta;
    }

    /**
     * Returns meta data for buttons container on the form.
     *
     * @return array
     */
    private function getButtonsSet()
    {
        return [
            'address_header' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'content' => $this->getAddressFieldSetLabel(),
                        ],
                    ],
                ]
            ]
        ];
    }

    /**
     * Returns additional meta data for customer addresses select.
     *
     * @return array
     */
    private function getAddressIdMeta()
    {
        return [
            static::CUSTOMER_ADDRESS_SELECT => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'issetShippingAddress' => $this->checkIfIssetShippingId(),
                            'visible' => $this->checkIfIssetShippingId(),
                            'addressesData' => $this->getCustomerAddressesData(),
                            'infoFieldSet' => static::INFO_FIELDSET_NAME,
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get fieldSets children meta data.
     *
     * @return array
     */
    private function getFieldSetsChildren()
    {
        $attributes = $this->getAddressAttributes();
        /** @var array $childrenData */
        $childrenData = [
            static::INFO_FIELDSET_NAME => [],
            static::ADDRESS_FIELDSET_NAME => [],
        ];
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
                        $this->getAttributeMeta($attribute, $sortOrder, $i )
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
    private function getAttributeMeta($attribute, $sortOrder, $attributeLine)
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
        $attributeMeta = array_merge_recursive(
            $attributeMeta,
            [
                'config' => [
                    'componentType' => 'field',
                    'placeholder' => __($elemLabel),
                    'additionalClasses' => $additionalClasses,
                    'validation' => $this->getValidation($attribute, $attributeLine),
                    'sortOrder' => $sortOrder,
                    'dataScope' => $elemName,
                    'imports' => $this->getImportsData($attributeCode)
                ],
            ]
        );

        //Additional meta data for attributes.
        $attributeMeta = $this->getPostCodeAttributeMeta($attributeCode, $attributeMeta);

        if ($attribute->getAttributeCode()=='region_id') {
            $attributeMeta = $this->getRegionIdAttributeMeta($attributeMeta);
        } else {
            $attributeMeta = array_merge_recursive(
                $attributeMeta,
                [
                    'config' => [
                        'formElement' => $formElement,
                        'dataType' => 'text',

                    ]
                ]
            );
        }

        $fieldSetName = $this->getFieldSetName($attributeCode);

        $result[$fieldSetName][$elemName]['arguments']['data'] = $attributeMeta;

        return $result;
    }

    /**
     * Returns additional meta data for post_code attribute.
     *
     * @param string $attributeCode
     * @param array $attributeMeta
     * @return array
     */
    private function getPostCodeAttributeMeta($attributeCode, $attributeMeta)
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
     * Returns additional meta data for region_id attribute.
     *
     * @param array $attributeMeta
     * @return array
     */
    private function getRegionIdAttributeMeta($attributeMeta)
    {
        $attributeMeta = array_merge_recursive(
            $attributeMeta,
            [
                'config' => [
                    'elementTmpl' => 'ui/form/element/select',
                    'customEntry' => 'region',
                    'component' => 'TNW_Subscriptions/js/form/subscription-profile/region',
                    'formElement' => 'select',
                    'filterBy' => [
                        'target' => '${ $.provider }:${ $.parentScope }.country_id',
                        'field' => 'country_id',
                    ],
                    'validation' => [
                        'required-entry' => (!($this->getShippingId() || $this->isShippingFieldSet())),
                    ],
                    'additionalClass' => ($this->checkIfIssetShippingId())? ' hidden': '',
                    'imports' => [
                        'checkValidation' => '!ns = ${ $.ns }, index = same_as_shipping:checked',
                        'checkVisibility' => 'ns = ${ $.ns }, index = country_id:value',
                    ],
                    'customScope' => 'region'
                ],
            ]
        );

        return $attributeMeta;
    }

    /**
     * Returns additional meta data for select/multiselect attributes.
     *
     * @param Attribute $attribute
     * @param array $attributeMeta
     * @param string $elemLabel
     * @param string $additionalClasses
     * @return array
     */
    private function getSourceAttributeMeta($attribute, $attributeMeta, $elemLabel, $additionalClasses)
    {
        if ($attribute->usesSource()) {
            $attributeMeta = array_merge_recursive(
                $attributeMeta,
                [
                    'options' => $attribute->getSource(),
                    'config' => [
                        'caption' => __($elemLabel),
                    ]
                ]
            );
            $additionalClasses = $additionalClasses . ' wide-select';
        }

        return array($attributeMeta, $additionalClasses);
    }

    /**
     * Checks if it is shipping address form.
     *
     * @return bool
     */
    private function isShippingFieldSet()
    {
        return $this->isShipping;
    }

    /**
     * Returns shipping|billing information fieldset dataScope.
     *
     * @return string
     */
    private function getInfoFieldSetDataScope()
    {
        if (!$this->infoFieldSetName) {
            $this->infoFieldSetName = self::SHIPPING_INFO_FIELDSET;
            if (!$this->isShippingFieldSet()) {
                $this->infoFieldSetName = self::BILLING_INFO_FIELDSET;
            }
        }

        return $this->infoFieldSetName;
    }

    /**
     * Returns shipping|billing address data fieldset dataScope.
     *
     * @return string
     */
    private function getAddressDataFieldSetdataScope()
    {
        if (!$this->addressDataFieldSetName) {
            $this->addressDataFieldSetName = self::SHIPPING_ADDRESS_FIELDSET;
            if (!$this->isShippingFieldSet()) {
                $this->addressDataFieldSetName = self::BILLING_ADDRESS_FIELDSET;
            }
        }

        return $this->addressDataFieldSetName;
    }

    /**
     * Returns label for customer address information fieldset.
     *
     * @return string
     */
    private function getInfoFieldSetLabel()
    {
        $infoFieldSetLabel = __('Shipping Information');
        if (!$this->isShippingFieldSet()) {
            $infoFieldSetLabel = __('Billing Information');
        }

        return $infoFieldSetLabel;
    }

    /**
     * Returns label for customer address data fieldset.
     *
     * @return string
     */
    private function getAddressFieldSetLabel()
    {
        $addressFieldSetLabel = __('Shipping Address');
        if (!$this->isShippingFieldSet()) {
            $addressFieldSetLabel = __('Billing Address');
        }

        return $addressFieldSetLabel;
    }

    /**
     * Returns customer address form attributes collection.
     *
     * @return \Magento\Customer\Model\ResourceModel\Form\Attribute\Collection
     */
    private function getAddressAttributes()
    {
        if (!$this->addressAttributes) {
            $this->addressAttributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
                AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                self::FORM_CODE
            );
        }

        return $this->addressAttributes;
    }

    /**
     * Get attribute validation rules.
     *
     * @param Attribute $attribute
     * @param int $attributeLine
     * @return array
     */
    private function getValidation($attribute, $attributeLine)
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
     * Retrieve type of form element for attribute from attribute frontendInput.
     *
     * @param Attribute $attribute
     * @return string
     */
    private function getFormElement($attribute)
    {
        $formElement = $attribute->getFrontendInput();

        if (isset($this->formElementsmapping[$attribute->getFrontendInput()])) {
            $formElement = $this->formElementsmapping[$attribute->getFrontendInput()];
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
    private function getElemNameAndLabel($attribute, $attributeLine)
    {
        $attributeCode = $attribute->getAttributeCode();
        $elemName = $attributeCode;
        $elemLabel = $attribute->getStoreLabel();

        //If there is multiline attribute we have to show all lines on the form.
        if ($attribute->getFrontendInput() == 'multiline') {
            $elemName = $attributeCode . $attributeLine;
            $elemLabel = $attribute->getStoreLabel() . ' ' . sprintf(__('(Line %s)'), $attributeLine + 1);
        }

        return array($elemName, $elemLabel);
    }

    /**
     * Returns attribute fieldSet name.
     *
     * @param string $attributeCode
     * @return string
     */
    private function getFieldSetName($attributeCode)
    {
        if (in_array($attributeCode, $this->infoAttributes)) {
            $fieldSetName = static::INFO_FIELDSET_NAME;
        } else {
            $fieldSetName = static::ADDRESS_FIELDSET_NAME;
        }

        return $fieldSetName;
    }

    /**
     * Returns additional classes for attribute from frontend model.
     *
     * @param Attribute $attribute
     * @return string
     */
    private function getAdditionalClasses($attribute)
    {
        $additionalClasses = $attribute->getFrontend()->getClass();

        return $additionalClasses;
    }

    /**
     * Checks if customer has at least one address.
     *
     * @return bool
     */
    private function checkIfIssetShippingId()
    {
        $return = true;
        if (!$this->getShippingId()) {
            $return = false;
        }

        return $return;
    }

    /**
     * Get meta data for 'imports' property of UI component on the form.
     *
     * @param string $attributeCode
     * @return array
     */
    private function getImportsData($attributeCode)
    {
        $imports = [];
        if (!in_array($attributeCode, $this->infoAttributes)) {

            if ($attributeCode != 'region_id') {
                if ($this->getShippingId() || $this->isShippingFieldSet()) {
                    $imports['visible'] = '!ns = ${ $.ns }, index = add_new_address_button:visible';
                } else {
                    $imports['visible'] = '!ns = ${ $.ns }, index = same_as_shipping:checked';
                }
            }

        } else {
            $imports['visible'] = '!${ $.parentName}.same_as_shipping:checked';
        }

        return $imports;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $getFromQuoteAddress = false;
        $dataArray = [];
        $addressId = $this->getShippingId();

        if ($addressId) {
            $dataObject = $this->addressRepository->getById($addressId);
            $dataArray = $this->addressMapper->toFlatArray($dataObject);
        } else {
            $customerId = $this->getCustomerId();
            if ($customerId) {
                $customerModel = $this->customerRepository->getById($customerId);
                $dataArray = $this->customerMapper->toFlatArray($customerModel);
            }

            $firstQuote = $this->session->getFirstQuote();

            if ($firstQuote) {
                $getFromQuoteAddress = true;
            }
        }

        if (count($dataArray) || $getFromQuoteAddress) {
            $data = array_merge_recursive(
                $data,
                $this->getAddressData($dataArray, $getFromQuoteAddress)
            );
        }

        if ($addressId) {
            $data = array_merge_recursive(
                $data,
                $this->modifyShippingIdData($addressId)
            );
        } else {
            if (!isset ($data[Account::FORM_DATA_VALUE][$this->getAddressDataFieldSetdataScope()]['country_id']) ||
            !$data[Account::FORM_DATA_VALUE][$this->getAddressDataFieldSetdataScope()]['country_id']) {
                $data = array_merge_recursive(
                    $data,
                    $this->modifyCountryIdData()
                );
            }
        }

        return $data;
    }

    /**
     * Returns necessary customer addresses data.
     *
     * @return array
     */
    private function getCustomerAddressesData()
    {
        $data = [];
        $customerId = $this->getCustomerId();
        if ($customerId) {
            /** @var \Magento\Customer\Api\Data\CustomerInterface $customerModel */
            $customerModel = $this->customerRepository->getById($customerId);
            $addresses = $customerModel->getAddresses();
            if (is_array($addresses) && count($addresses) > 0) {
                /** @var \Magento\Customer\Api\Data\AddressInterface $address */
                foreach ($addresses as $address) {
                    /** @var array $addressData */
                    $addressData = $this->addressMapper->toFlatArray($address);
                    $addressData = $this->getAddressData($addressData, false);
                    $customerInfo = $addressData[Account::FORM_DATA_VALUE][$this->getInfoFieldSetDataScope()];
                    $data[$address->getId()] = $customerInfo;
                }
            }
        }

        return $data;
    }


    /**
     * Returns necessary customer address data.
     *
     * @param array $addressData
     * @param bool $getFromQuoteAddress
     * @return array
     */
    private function getAddressData($addressData, $getFromQuoteAddress)
    {
        $infoArray = [];
        $addressDataArray = [];

        if ($getFromQuoteAddress) {
            //If there is a quote and we retrieve some address data from quote address we have to devide it
            //for two fieldsets.
            $firstQuote = $this->session->getFirstQuote();
            $customerAddressDataObject = $firstQuote->getShippingAddress()->exportCustomerAddress();
            $addressData = array_merge(
                $addressData,
                $this->addressMapper->toFlatArray($customerAddressDataObject)
            );

            foreach ($this->getAddressAttributes() as $addressAttribute) {
                $attributeCode = $addressAttribute->getAttributeCode();

                $attributeData = $this->getAddressAttributeData($addressData, $addressAttribute);

                if (in_array($attributeCode, $this->infoAttributes)) {
                    $infoArray = array_merge_recursive($infoArray, $attributeData);
                } else {
                    $addressDataArray = array_merge_recursive($addressDataArray, $attributeData);
                }
            }
        } else {
            foreach ($this->infoAttributes as $attributeCode) {
                /** @var string $attributeCode */
                if (isset($addressData[$attributeCode])) {
                    $infoArray[$attributeCode] = $addressData[$attributeCode];
                }
            }
        }

        return [
            Account::FORM_DATA_VALUE => [
                $this->getInfoFieldSetDataScope() => $infoArray,
                $this->getAddressDataFieldSetdataScope() => $addressDataArray,
            ]
        ];
    }

    /**
     * Returns selected $shipping address Id.
     *
     * @param $addressId
     * @return array
     */
    private function modifyShippingIdData($addressId)
    {
        return [
            Account::FORM_DATA_VALUE => [
                $this->getAddressDataFieldSetdataScope() => [
                    static::CUSTOMER_ADDRESS_SELECT => $addressId
                ]
            ]
        ];
    }

    /**
     * Returns default country id.
     *
     * @return array
     */
    private function modifyCountryIdData()
    {
        return [
            Account::FORM_DATA_VALUE => [
                $this->getAddressDataFieldSetdataScope() => [
                    'country_id' => 'US'
                ]
            ]
        ];
    }

    /**
     * Returns customerId from session.
     *
     * @return null|integer|string
     */
    private function getCustomerId()
    {
        $customerId = null;
        if ($this->session->getCustomerId()) {
            $customerId = $this->session->getCustomerId();
        }

        return $customerId;
    }

    /**
     * Returns shipping address Id.
     *
     * If customer has default shipping address - returns it's id.
     * If customer doesn't have default shipping address, but he has array of addresses -
     * returns the first item of array.
     * If there is new customer - returns null.
     *
     * @return int|null|string
     */
    private function getShippingId()
    {
        if ($this->addressId === false) {
            $addressId = null;
            $customerId = $this->getCustomerId();
            if ($customerId) {
                $customerModel = $this->customerRepository->getById($customerId);
                $addressId = $customerModel->getDefaultShipping();

                if (!$addressId) {
                    $customerAddresses = $customerModel->getAddresses();
                    $customerAddress = array_shift($customerAddresses);
                    if ($customerAddress instanceof \Magento\Customer\Model\Data\Address) {
                        $addressId = $customerAddress->getId();
                    }
                }
            }

            $this->addressId = $addressId;
        }

        return $this->addressId;
    }

    /**
     * Returns address attribute data from customer address or quote address.
     *
     * @param array $addressData
     * @param Attribute $addressAttribute
     * @return array
     */
    private function getAddressAttributeData($addressData, $addressAttribute)
    {
        $attributeData = [];
        $attributeCode = $addressAttribute->getAttributeCode();
        //To show all necessary data for multiline attributes we need to add cycle.
        if ($addressAttribute->getFrontendInput() == 'multiline') {
            $lineCount = $addressAttribute->getMultilineCount();
            $i = 0;
                do {
                    if (isset($addressData[$attributeCode][$i])) {
                        $attributeLineData = [
                            $attributeCode . $i => $addressData[$attributeCode][$i]
                        ];
                    } else {
                        $attributeLineData = [
                            $attributeCode . $i => '',
                        ];
                    }

                    $attributeData = array_merge($attributeData, $attributeLineData);
                    $i++;
                    $lineCount--;
                } while ($lineCount > 0);
        } elseif (isset($addressData[$attributeCode])) {
            $attributeData = [
                $attributeCode => $addressData[$attributeCode]
            ];
        } elseif ($attributeCode != 'country_id') {
            $attributeData = [
                $attributeCode => ''
            ];
        }

        return $attributeData;
    }
}
