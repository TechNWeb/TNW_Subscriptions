<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Checkout;

class AddressProcessor implements LayoutProcessorInterface
{
    /**
     * @var \Magento\Customer\Model\AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var \Magento\Ui\Component\Form\AttributeMapper
     */
    private $attributeMapper;

    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @var \Magento\Customer\Helper\Address
     */
    private $addressHelper;

    /**
     * @var array
     */
    private static $formElementMap = [
        'checkbox' => 'Magento_Ui/js/form/element/select',
        'select' => 'Magento_Ui/js/form/element/select',
        'textarea' => 'Magento_Ui/js/form/element/textarea',
        'multiline' => 'Magento_Ui/js/form/components/group',
        'multiselect' => 'Magento_Ui/js/form/element/multiselect',
        'image' => 'Magento_Ui/js/form/element/media',
        'file' => 'Magento_Ui/js/form/element/media',
    ];

    /**
     * Map template
     *
     * @var array
     */
    protected static $templateMap = [
        'image' => 'media',
        'file' => 'media',
    ];

    public function __construct(
        \Magento\Customer\Model\AttributeMetadataDataProvider $attributeMetadataDataProvider,
        \Magento\Ui\Component\Form\AttributeMapper $attributeMapper,
        \Magento\Framework\Stdlib\ArrayManager $arrayManager,
        \Magento\Customer\Helper\Address $addressHelper
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->arrayManager = $arrayManager;
        $this->addressHelper = $addressHelper;
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($jsLayout)
    {
        $fieldset = 'components/checkout/children/steps/children/shipping/children/shippingAddress/children/shipping-address-fieldset/children';
        foreach ($this->addressAttributes() as $attributeCode => $attributeConfig) {
            $additionalConfig = $this->arrayManager->get("$fieldset/$attributeCode", $jsLayout, []);
            if (!$this->isFieldVisible($attributeCode, $attributeConfig, $additionalConfig)) {
                continue;
            }

            $jsLayout = $this->arrayManager->set(
                "$fieldset/$attributeCode",
                $jsLayout,
                array_replace_recursive(
                    $this->getFieldConfig($attributeCode, $attributeConfig),
                    $additionalConfig
                )
            );
        }

        $fieldset = 'components/checkout/children/steps/children/payment/children/payment-address-fieldset/children';
        foreach ($this->addressAttributes() as $attributeCode => $attributeConfig) {
            $additionalConfig = $this->arrayManager->get("$fieldset/$attributeCode", $jsLayout, []);
            if (!$this->isFieldVisible($attributeCode, $attributeConfig, $additionalConfig)) {
                continue;
            }

            $jsLayout = $this->arrayManager->set(
                "$fieldset/$attributeCode",
                $jsLayout,
                array_replace_recursive(
                    $this->getFieldConfig($attributeCode, $attributeConfig),
                    $additionalConfig
                )
            );
        }

        return $jsLayout;
    }

    /**
     * @param string $attributeCode
     * @param array $attributeConfig
     *
     * @return array
     */
    protected function getFieldConfig($attributeCode, array $attributeConfig)
    {
        if (strcasecmp($attributeConfig['formElement'], 'multiline') === 0) {
            return $this->getMultilineFieldConfig($attributeCode, $attributeConfig);
        }

        return [
            'component' => self::$formElementMap[$attributeConfig['formElement']] ?? 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => 'shippingAddress',
                'template' => 'ui/form/field',
                'elementTmpl' => isset(self::$templateMap[$attributeConfig['formElement']])
                    ? 'ui/form/element/' . self::$templateMap[$attributeConfig['formElement']]
                    : 'ui/form/element/' . $attributeConfig['formElement'],
            ],
            'dataScope' => 'shippingAddress.' . $attributeCode,
            'label' => __($attributeConfig['label']),
            'provider' => 'checkoutProvider',
            'sortOrder' => $attributeConfig['sortOrder'],
            'validation' => $attributeConfig['validation'],
            'options' => $attributeConfig['options'] ?? [],
        ];
    }

    /**
     * @param string $attributeCode
     * @param array $attributeConfig
     *
     * @return array
     */
    private function getMultilineFieldConfig($attributeCode, array $attributeConfig)
    {
        $lines = [];
        unset($attributeConfig['validation']['required-entry']);
        for ($lineIndex = 0; $lineIndex < (int)$attributeConfig['size']; $lineIndex++) {
            $isFirstLine = $lineIndex === 0;
            $line = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    // customScope is used to group elements within a single form e.g. they can be validated separately
                    'customScope' => 'shippingAddress',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/input'
                ],
                'dataScope' => $lineIndex,
                'provider' => 'checkoutProvider',
                'validation' => $isFirstLine
                    ? array_merge(
                        ['required-entry' => (bool)$attributeConfig['required']],
                        $attributeConfig['validation']
                    )
                    : $attributeConfig['validation'],
                'additionalClasses' => $isFirstLine ? 'field' : 'additional'

            ];
            if ($isFirstLine && isset($attributeConfig['default']) && $attributeConfig['default'] != null) {
                $line['value'] = $attributeConfig['default'];
            }
            $lines[] = $line;
        }

        return [
            'component' => 'Magento_Ui/js/form/components/group',
            'config' => [
                'template' => 'ui/group/group',
                'additionalClasses' => $attributeCode
            ],
            'label' => $attributeConfig['label'],
            'required' => (bool)$attributeConfig['required'],
            'dataScope' => 'shippingAddress.' . $attributeCode,
            'provider' => 'checkoutProvider',
            'sortOrder' => $attributeConfig['sortOrder'],
            'type' => 'group',
            'children' => $lines,
        ];
    }

    /**
     * Check if address attribute is visible on frontend
     *
     * @param string $attributeCode
     * @param array $attributeConfig
     * @param array $additionalConfig field configuration provided via layout XML
     * @return bool
     */
    private function isFieldVisible($attributeCode, array $attributeConfig, array $additionalConfig = [])
    {
        if (!$attributeConfig['visible'] || (isset($additionalConfig['visible']) && !$additionalConfig['visible'])) {
            return false;
        }

        if (strcasecmp($attributeCode, 'vat_id') === 0 && !$this->addressHelper->isVatAttributeVisible()) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addressAttributes()
    {
        /** @var \Magento\Eav\Api\Data\AttributeInterface[] $attributes */
        $attributes = $this->attributeMetadataDataProvider
            ->loadAttributesCollection('customer_address', 'customer_register_address');

        $elements = [];
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if ($attribute->getIsUserDefined()) {
                continue;
            }

            $elements[$code] = $this->attributeMapper->map($attribute);
        }

        return $elements;
    }
}
