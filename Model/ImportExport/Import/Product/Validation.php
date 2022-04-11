<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ImportExport\Import\Product;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Validator;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Validation add validation for tnw discount amount
 */
class Validation extends Validator
{
    const ERROR_INVALID_DISCOUNT_AMOUNT = 'Discount amount can not be equal or less than product price';
    const ERROR_INVALID_DEFAULT_BILLING_FREQUENCY = 'Default Billing frequency must be type of bool (1 or 0)';
    const ERROR_INVALID_REGULAR_PRICE = 'Regular price can not be equal or less than 0';
    const ERROR_INVALID_INITIAL_FEE = 'Initial fee can not be less than 0';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Validation constructor.
     * @param StringUtils $string
     * @param SerializerInterface $serializer
     * @param array $validators
     */
    public function __construct(
        StringUtils $string,
        SerializerInterface $serializer,
        $validators = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($string, $validators);
    }

    /**
     * Is attribute valid
     *
     * @param string $attrCode
     * @param array $attrParams
     * @param array $rowData
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function isAttributeValid($attrCode, array $attrParams, array $rowData)
    {
        $this->_rowData = $rowData;
        if (isset($rowData['product_type'])
            && !empty($attrParams['apply_to'])
            && !in_array($rowData['product_type'], $attrParams['apply_to'])
        ) {
            return true;
        }

        if (!$this->isRequiredAttributeValid($attrCode, $attrParams, $rowData)) {
            $valid = false;
            $this->_addMessages(
                [sprintf(
                    $this->context->retrieveMessageTemplate(
                        RowValidatorInterface::ERROR_VALUE_IS_REQUIRED
                    ),
                    $attrCode
                )
                ]
            );
            return $valid;
        }

        if (!strlen(trim($rowData[$attrCode]))) {
            return true;
        }

        if ($rowData[$attrCode] === $this->context->getEmptyAttributeValueConstant() && !$attrParams['is_required']) {
            return true;
        }

        switch ($attrParams['type']) {
            case 'varchar':
            case 'text':
                $valid = $this->textValidation($attrCode, $attrParams['type']);
                break;

            case 'decimal':
            case 'int':
                $valid = $this->numericValidation($attrCode, $attrParams['type']);
                break;

            case 'select':
            case 'boolean':
                $valid = $this->validateOption($attrCode, $attrParams['options'], $rowData[$attrCode]);
                break;

            case 'multiselect':
                $values = $this->context->parseMultiselectValues($rowData[$attrCode]);
                foreach ($values as $value) {
                    $valid = $this->validateOption($attrCode, $attrParams['options'], $value);
                    if (!$valid) {
                        break;
                    }
                }

                $uniqueValues = array_unique($values);
                if (count($uniqueValues) != count($values)) {
                    $valid = false;
                    $this->_addMessages([RowValidatorInterface::ERROR_DUPLICATE_MULTISELECT_VALUES]);
                }
                break;

            case 'datetime':
                $val   = trim($rowData[$attrCode]);
                $valid = strtotime($val) !== false;
                if (!$valid) {
                    $this->_addMessages([RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_TYPE]);
                }
                break;

            default:
                $valid = true;
                break;
        }

        if ($valid && !empty($attrParams['is_unique'])) {
            if (isset($this->_uniqueAttributes[$attrCode][$rowData[$attrCode]])
                && ($this->_uniqueAttributes[$attrCode][$rowData[$attrCode]] != $rowData[Product::COL_SKU])
            ) {
                $this->_addMessages([RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE]);
                return false;
            }

            $this->_uniqueAttributes[$attrCode][$rowData[$attrCode]] = $rowData[Product::COL_SKU];
        }

        if ($attrCode == 'tnw_subscr_discount_amount') {
            if ($rowData['tnw_subscr_discount_type'] == 'Flat Fee') {
                if ($rowData['price'] == $rowData['tnw_subscr_discount_amount']
                    || $rowData['tnw_subscr_discount_amount'] < 0
                ) {
                    $valid = false;
                    $this->_addMessages([self::ERROR_INVALID_DISCOUNT_AMOUNT]);
                }
            }

            if ($rowData['tnw_subscr_discount_type'] == 'Percent') {
                if ($rowData['tnw_subscr_discount_amount'] == 100
                    || $rowData['tnw_subscr_discount_amount'] < 0
                ) {
                    $valid = false;
                    $this->_addMessages([self::ERROR_INVALID_DISCOUNT_AMOUNT]);
                }
            }

            if ($rowData['tnw_subscr_billing_frequency']) {
                $billingFrequency = $this->serializer->unserialize($rowData['tnw_subscr_billing_frequency']);
                $valid            = null;
                foreach ($billingFrequency as $item) {
                    if ($item['default_billing_frequency'] != '0'
                        && $item['default_billing_frequency'] != '1'
                    ) {
                        $valid = false;
                        $this->_addMessages([self::ERROR_INVALID_DEFAULT_BILLING_FREQUENCY]);
                    }

                    if ($item['price'] < 0 || $item['price'] == 0) {
                        $valid = false;
                        $this->_addMessages([self::ERROR_INVALID_REGULAR_PRICE]);
                    }

                    if ($item['initial_fee'] < '0.00') {
                        $valid = false;
                        $this->_addMessages([self::ERROR_INVALID_INITIAL_FEE]);
                    }
                }
            }
        }

        if (!$valid) {
            $this->setInvalidAttribute($attrCode);
        }

        return $valid;
    }

    /**
     * Check if value is valid attribute option
     *
     * @param string $attrCode
     * @param array $possibleOptions
     * @param string $value
     * @return bool
     */
    private function validateOption($attrCode, $possibleOptions, $value)
    {
        if (!isset($possibleOptions[strtolower($value)])) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION
                        ),
                        $attrCode
                    )
                ]
            );
            return false;
        }
        return true;
    }
}
