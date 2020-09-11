<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use Magento\Customer\Model\Customer;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Ui\Component\Form;
use Magento\Customer\Model\Address\Mapper as AddressMapper;
use Magento\Framework\Json\Encoder;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Data provider for summary form.
 */
class SummaryAddressForm extends AbstractDataProvider
{
    /**
     * Form data scope.
     */
    const DATA_SCOPE_SUMMARY_SHIPPING_ADDRESS_FORM = 'tnw_subscriptionprofile_summary_shipping_address_form';
    const DATA_SCOPE_SUMMARY_BILLING_ADDRESS_FORM = 'tnw_subscriptionprofile_summary_billing_address_form';

    const SHIPPING_INFORMATION_FIELDSET = 'shipping_information';
    const BILLING_INFORMATION_FIELDSET = 'billing_information';

    const BILLING_INFORMATION_HEADER = 'billing_information_header';
    const SHIPPING_INFORMATION_HEADER = 'shipping_information_header';
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
    const SHIPPING_ADDRESS_FIELDSET_NAME = 'shipping_address_fields';
    const BILLING_ADDRESS_FIELDSET_NAME = 'billing_address_fields';
    /**#@-*/

    /**
     * Select form component with customer addresses list.
     */
    const CUSTOMER_SHIPPING_ADDRESS_SELECT = 'customer_shipping_address_id';
    const CUSTOMER_BILLING_ADDRESS_SELECT = 'customer_billing_address_id';

    /**#@+
     * Edit Buttons Names
     */
    const SHIPPING_ADDRESS_EDIT_BUTTON = 'edit_shipping_address';
    const BILLING_ADDRESS_EDIT_BUTTON = 'edit_billing_address';
    /**#@-*/

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

    /**
     * Converts Address Service Data Object to an array.
     *
     * @var AddressMapper
     */
    private $addressMapper;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Encoder
     */
    private $jsonEncoder;

    /**
     * SummaryForm constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param AddressMapper $addressMapper
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param RequestInterface $request
     * @param Encoder $encoder
     * @param $isShipping
     * @param array $meta
     * @param array $data
     * @internal param Registry $registry
     * @internal param $isShipping
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        AddressMapper $addressMapper,
        SubscriptionProfileRepositoryInterface $profileRepository,
        RequestInterface $request,
        Encoder $encoder,
        $isShipping,
        array $meta = [],
        array $data = []
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->addressMapper = $addressMapper;
        $this->profileRepository = $profileRepository;
        $this->request = $request;
        $this->jsonEncoder = $encoder;
        $this->isShipping = $isShipping;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data =  [];
        $addressId = $this->getAddressId();

        if (!empty($addressId)) {
            $dataArray = $this->addressMapper->toFlatArray($this->getProfileAddress()->exportCustomerAddress());
            if (count($dataArray)) {
                $data = array_replace_recursive($data, $this->getAddressData($dataArray, true));
            }

            $data = array_replace_recursive($data, $this->modifyAddressIdData($addressId));

            if (empty($data[$this->getAddressDataFieldSetdataScope()]['country_id'])) {
                $data = array_replace_recursive($data, $this->modifyCountryIdData());
            }

            $data[SummaryInsertForm::FORM_DATA_KEY] = $this->getProfileId();
        }

        return [
            $this->getProfileId() => $data
        ];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        $fieldSetsChildren = $this->getFieldSetsChildren();
        $addressFieldSetName = $this->getAddressFieldsetName();
        $addressInfoFieldSetName = $this->getAddressInfoFieldsetName();

        $meta = array_merge_recursive(
            $meta,
            [
                $addressInfoFieldSetName => [
                    'children' => array_merge(
                        $this->getButtonsSet(),
                        [
                            static::INFO_FIELDSET_NAME => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'label' => false,
                                            'collapsible' => false,
                                            'componentType' => Form\Fieldset::NAME,
                                            'sortOrder' => 20,
                                            'dataScope' => $this->getInfoFieldSetDataScope(),
                                            'imports' => [
                                                'visible' => '!ns = ${ $.ns }, index = ' .
                                                    $addressInfoFieldSetName . ':preview',
                                                '__disableTmpl' => [
                                                    'visible' => false
                                                ]
                                            ]
                                        ],
                                    ],
                                ],
                                'children' => $fieldSetsChildren[static::INFO_FIELDSET_NAME],
                            ],
                            $addressFieldSetName => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'dataScope' => $this->getAddressDataFieldSetdataScope(),
                                            'customerAddressesData' => $this->getCustomerAddressesData(),
                                        ],
                                    ],
                                ],
                                'children' => array_merge(
                                    $fieldSetsChildren[$addressFieldSetName],
                                    $this->getAddressIdMeta()
                                )
                            ],
                        ]
                    ),
                ],
            ]
        );

        return $meta;
    }

    /**
     * Returns additional meta data for customer addresses select.
     *
     * @return array
     */
    private function getAddressIdMeta()
    {
        return [
            $this->getCustomerAddressSelect() => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'hasAddress' => $this->hasCustomerAddresses(),
                            'visible' => (bool)$this->getAddressId(),
                            'addressesData' => $this->getCustomerShippingInformationData(),
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
            $this->getAddressFieldsetName() => [
                'address_header' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'content' => $this->getAddressFieldSetLabel(),
                        ],
                    ],
                ]
            ]],
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
            $elementFormData = [
                'config' => [
                    'formElement' => $formElement,
                    'dataType' => 'text',

                ]
            ];

            $additionalElementFormData = [];
            if (in_array($attribute->getAttributeCode(), $this->infoAttributes)) {
                $currentFieldSetIndex = ($this->isShippingFieldSet())
                    ? self::SHIPPING_ADDRESS_FIELDSET_NAME : self::BILLING_ADDRESS_FIELDSET_NAME;
                $additionalElementFormData = [
                    'config' => [
                        'component' => 'TNW_Subscriptions/js/form/subscription-profile/shipping-information-input',
                        'addressFieldsetIndex' => $currentFieldSetIndex
                    ]
                ];
            }

            $attributeMeta = array_merge_recursive($attributeMeta, $elementFormData, $additionalElementFormData);
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
                    'customerAddressSelector' => $this->getCustomerAddressSelect(),
                    'formElement' => 'select',
                    'filterBy' => [
                        'target' => '${ $.provider }:${ $.parentScope }.country_id',
                        'field' => 'country_id',
                        '__disableTmpl' => [
                            'target' => false
                        ]
                    ],
                    'validation' => [
                        'required-entry' => (!($this->getAddressId())),
                    ],
                    'additionalClass' => ($this->getAddressId())? ' hidden': '',
                    'imports' => [
                        'checkVisibility' => 'ns = ${ $.ns }, index = country_id:value',
                        '__disableTmpl' => [
                            'checkVisibility' => false
                        ]
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
            $fieldSetName = $this->getAddressFieldsetName();
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
                $imports['visible'] = '!ns = ${ $.ns }, index = add_new_address_button:visible';
                $imports['__disableTmpl'] = ['visible' => false];
            }
        }

        return $imports;
    }

    /**
     * Returns necessary customer addresses data.
     *
     * @return array
     */
    private function getCustomerShippingInformationData()
    {
        $data = [];
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customerModel */
        $customerModel = $this->getCustomer();
        if ($customerModel) {
            $addresses = $customerModel->getAddresses();
            if (is_array($addresses) && count($addresses) > 0) {
                /** @var \Magento\Customer\Api\Data\AddressInterface $address */
                foreach ($addresses as $address) {
                    /** @var array $addressData */
                    $addressData = $this->addressMapper->toFlatArray($address);
                    $addressData = $this->getAddressData($addressData);
                    $customerInfo = $addressData[$this->getInfoFieldSetDataScope()];
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
     * @param $getFromProfileAddress
     * @return array
     */
    private function getAddressData($addressData, $getFromProfileAddress = false)
    {
        $infoArray = [];
        $addressDataArray = [];

        if ($getFromProfileAddress) {
            /** @var SubscriptionProfileAddressInterface $profileAddress */
            $profileAddress = $this->getProfileAddress();
            if ($profileAddress) {
                $customerAddressDataObject = $profileAddress->exportCustomerAddress();
                $addressData = array_merge(
                    $addressData,
                    $this->addressMapper->toFlatArray($customerAddressDataObject)
                );

            }

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
            $this->getInfoFieldSetDataScope() => $infoArray,
            $this->getAddressDataFieldSetdataScope() => $addressDataArray,
        ];
    }

    /**
     * Returns selected address Id.
     *
     * @param $addressId
     * @return array
     */
    private function modifyAddressIdData($addressId)
    {
        return [
            $this->getAddressDataFieldSetdataScope() => [
                $this->getCustomerAddressSelect() => $addressId
            ]
        ];
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
    private function getAddressId()
    {
        if ($this->addressId === false) {
            $addressId = $this->getProfileAddress()->getCustomerAddressId();
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

    /**
     * Returns default country id.
     *
     * @return array
     */
    private function modifyCountryIdData()
    {
        return [
            $this->getAddressDataFieldSetdataScope() => [
                'country_id' => 'US'
            ]
        ];
    }

    /**
     * Returns current subscription profile from request param.
     *
     * @return SubscriptionProfile|null
     */
    private function getProfile()
    {
        $profileId = (int)$this->request->getParam(SummaryInsertForm::FORM_DATA_KEY, 0);
        try {
            /** @var SubscriptionProfile $profile */
            $profile = $this->profileRepository->getById($profileId);
        } catch (\Exception $e) {
            $profile = null;
        }

        return $profile;
    }

    /**
     * Returns current subscription profile id from registry
     *
     * @return mixed|null|string
     */
    private function getProfileId()
    {
        return $this->getProfile() ? $this->getProfile()->getId() : null;
    }

    /**
     * @return mixed|null|SubscriptionProfileAddressInterface
     */
    private function getProfileAddress()
    {
        /** @var SubscriptionProfile $profile */
        $profile = $this->getProfile();
        $address = null;
        if ($profile) {
            $address =  $this->isShippingFieldSet()
                ? $profile->getShippingAddress()
                : $profile->getBillingAddress();
        }
        return $address;
    }

    /**
     * Returns customer from subscription profile.
     *
     * @return Customer
     */
    private function getCustomer()
    {
        return $this->getProfile() ? $this->getProfile()->getCustomer() : null;
    }

    /**
     * Checks if customer has any address.
     *
     * @return bool
     */
    private function hasCustomerAddresses()
    {
        $result = false;
        $customer = $this->getCustomer();

        if (null !== $customer) {
            $result = !empty($customer->getAddresses());
        }

        return $result;
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
        return $this->isShippingFieldSet()
            ? self::SHIPPING_INFO_FIELDSET
            : self::BILLING_INFO_FIELDSET;
    }

    /**
     * Returns shipping|billing address data fieldset dataScope.
     *
     * @return string
     */
    private function getAddressDataFieldSetdataScope()
    {
        return $this->isShippingFieldSet()
            ? self::SHIPPING_ADDRESS_FIELDSET
            : self::BILLING_ADDRESS_FIELDSET;
    }

    /**
     * Returns label for customer address information fieldset.
     *
     * @return string
     */
    private function getInfoFieldSetLabel()
    {
        return $this->isShippingFieldSet()
            ? __('Shipping Information')
            : __('Billing Information');
    }

    /**
     * Returns label for customer address data fieldset.
     *
     * @return string
     */
    private function getAddressFieldSetLabel()
    {
        return $this->isShippingFieldSet()
            ? __('Shipping Address')
            : __('Billing Address');
    }

    /**
     * Returns address fieldset name depends on "isShipping" param
     *
     * @return string
     */
    private function getAddressFieldsetName()
    {
        return $this->isShippingFieldSet()
            ? self::SHIPPING_ADDRESS_FIELDSET_NAME
            : self::BILLING_ADDRESS_FIELDSET_NAME;
    }

    /**
     * Returns address info fieldset name depends on "isShipping" param
     *
     * @return string
     */
    private function getAddressInfoFieldsetName()
    {
        return $this->isShippingFieldSet()
            ? self::SHIPPING_INFORMATION_FIELDSET
            : self::BILLING_INFORMATION_FIELDSET;
    }

    /**
     * Returns address info header name depends on "isShipping" param
     *
     * @return string
     */
    private function getAddressInfoHeaderName()
    {
        return $this->isShippingFieldSet()
            ? self::SHIPPING_INFORMATION_HEADER
            : self::BILLING_INFORMATION_HEADER;
    }

    /**
     * Returns address edit button name depends on "isShipping" param
     *
     * @return string
     */
    private function getEditAddressButtonName()
    {
        return $this->isShippingFieldSet()
            ? self::SHIPPING_ADDRESS_EDIT_BUTTON
            : self::BILLING_ADDRESS_EDIT_BUTTON;
    }

    /**
     * Return customer address select id
     *
     * @return string
     */
    private function getCustomerAddressSelect()
    {
        return $this->isShippingFieldSet()
            ? self::CUSTOMER_SHIPPING_ADDRESS_SELECT
            : self::CUSTOMER_BILLING_ADDRESS_SELECT;
    }

    /**
     * Returns meta data for buttons container on the form.
     *
     * @return array
     */
    private function getButtonsSet()
    {
        $subscriptionProfile = $this->getProfile();
        $isEditVisible = $subscriptionProfile && $subscriptionProfile->canEditProfile();

        $editButtonConfig = [
            'config' => [
                'visible' => $isEditVisible,
                'imports' => [
                    'visible' => $isEditVisible ?
                        'ns = ${ $.ns }, index = ' . $this->getAddressInfoFieldsetName() . ':preview' : '',
                    '__disableTmpl' => [
                        'visible' => false
                    ]
                ]
            ],
        ];

        return [
            $this->getAddressInfoHeaderName() => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'content' => $this->getInfoFieldSetLabel(),
                        ],
                    ],
                ],
                'children' => [
                    $this->getEditAddressButtonName() => [
                        'arguments' => [
                            'data' => $editButtonConfig,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Retrieve customer addresses data to display.
     *
     * @return string
     */
    private function getCustomerAddressesData()
    {
        $result = [];
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customerModel */
        $customerModel = $this->getCustomer();
        if ($customerModel) {
            $addressesList = $customerModel->getAddresses();

            foreach ($addressesList as $address) {
                $streetData = [];
                if ($address->getStreet()) {
                    foreach ($address->getStreet() as $key => $streetValue) {
                        $streetKey = 'street' . $key;
                        $streetData[$streetKey] = $streetValue;
                    }
                }

                $addressData = [
                    'firstname' => $address->getFirstname(),
                    'lastname' => $address->getLastname(),
                    'company' => $address->getCompany(),
                    'telephone' => $address->getTelephone(),
                    'city' => $address->getCity(),
                    'country_id' => $address->getCountryId(),
                    'region' => $address->getRegion()->getRegion(),
                    'region_id' => $address->getRegionId(),
                    'postcode' => $address->getPostcode(),
                    'fax' => $address->getFax(),
                    'vat_id' => $address->getVatId()
                ];

                $result[$address->getId()] = array_merge($addressData, $streetData);

            }
        }

        return str_replace('"', "'", $this->jsonEncoder->encode($result));
    }
}
