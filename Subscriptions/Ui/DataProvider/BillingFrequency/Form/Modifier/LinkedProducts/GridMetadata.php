<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Form\Modifier\LinkedProducts;

use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Phrase;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use Magento\Framework\Registry;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\Discount;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\LockPrice;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\UnlockPresetQty;

/**
 * Class Metadata for LinkedProducts
 */
class GridMetadata
{
    /**
     * Billing Frequency unit type.
     *
     * BillingFrequencyUnitType
     */
    private $billingFrequencyUnitType;

    /**
     * Store Manager.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Registry.
     *
     * @var Registry
     */
    private $registry;

    /**
     * @param BillingFrequencyUnitType $billingFrequencyUnitType
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     */
    public function __construct(
        BillingFrequencyUnitType $billingFrequencyUnitType,
        StoreManagerInterface $storeManager,
        Registry $registry
    ) {
        $this->billingFrequencyUnitType = $billingFrequencyUnitType;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
    }

    /**
     * Retrieve meta column.
     *
     * @return array
     */
    public function fillMeta()
    {
        return array_merge(
            $this->getArrayColumns(),
            [
                'actionDelete' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'additionalClasses' => 'data-grid-actions-cell',
                                'componentType' => \Magento\Ui\Component\Form\Element\ActionDelete::NAME,
                                'dataType' => Text::NAME,
                                'label' => __('Actions'),
                                'sortOrder' => 300,
                                'fit' => true,
                            ],
                        ],
                    ],
                ],
                'position' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'dataType' => Number::NAME,
                                'formElement' => Input::NAME,
                                'componentType' => Field::NAME,
                                'dataScope' => 'position',
                                'sortOrder' => 80,
                                'visible' => false,
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Get label for price column.
     *
     * @return string
     */
    private function getPriceLabel()
    {
        $priceLabel = 'Price';

        /** @var ProductBillingFrequencyInterface $frequency */
        $frequency = $this->registry->registry('tnw_subscriptions_billingfrequency');

        if ($frequency && $frequency->getId()) {
            $unit = $this->billingFrequencyUnitType->getLabelByValue($frequency->getUnit());

            $priceLabel = $frequency->getFrequency() . ' ' . $unit . ' ' . $priceLabel;
        }

        return $priceLabel;
    }

    /**
     * Get currency symbol.
     *
     * @return string
     */
    private function getCurrencySymbol()
    {
        return $this->storeManager->getStore()->getBaseCurrency()->getCurrencySymbol();
    }

    /**
     * Retrieve column structure.
     *
     * @param string $dataScope
     * @param bool $fit
     * @param Phrase $label
     * @param int $sortOrder
     *
     * @return array
     */
    private function getColumnBaseData(
        $dataScope,
        $fit,
        Phrase $label,
        $sortOrder
    ) {
        $column = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'dataType' => Text::NAME,
                        'dataScope' => $dataScope,
                        'fit' => $fit,
                        'label' => $label,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];

        return $column;
    }

    /**
     * Get custom data for column.
     *
     * @param array $column
     * @param string $component
     * @param string $elementTmpl
     * @param array $service
     * @param null|string $addBefore
     * @param null|string $addAfter
     * @param null|Phrase $notice
     * @param bool $visible
     * @param array $imports
     *
     * @return array
     */
    private function setColumnSpecialData(
        array $column,
        $component = 'Magento_Ui/js/form/element/text',
        $elementTmpl = 'ui/dynamic-rows/cells/text',
        $service = [],
        $addBefore = null,
        $addAfter = null,
        Phrase $notice = null,
        $visible = true,
        $imports = []
    ) {
        $component ? $column['arguments']['data']['config']['component'] = $component : null;
        $elementTmpl ? $column['arguments']['data']['config']['elementTmpl'] = $elementTmpl : null;
        $service ? $column['arguments']['data']['config']['service'] = $service : null;
        $addBefore ? $column['arguments']['data']['config']['addbefore'] = $addBefore : null;
        $addAfter ? $column['arguments']['data']['config']['addafter'] = $addAfter : null;
        $notice ? $column['arguments']['data']['config']['notice'] = $notice : null;
        $visible ?: $column['arguments']['data']['config']['visible'] = $visible;
        $imports ? $column['arguments']['data']['config']['imports'] = $imports : null;

        return $column;
    }

    /**
     * Get array of columns.
     *
     * @return array
     */
    private function getArrayColumns()
    {
        $idColumn = $this->getColumnBaseData('id', true, __('ID'), 0);
        $idColumn = $this->setColumnSpecialData($idColumn);

        $imageColumn = $this->getColumnBaseData('thumbnail', true, __('Image'), 10);
        $imageColumn = $this->setColumnSpecialData(
            $imageColumn,
            '',
            'ui/dynamic-rows/cells/thumbnail'
        );

        $nameColumn = $this->getColumnBaseData('name', false, __('Name'), 20);
        $nameColumn = $this->setColumnSpecialData($nameColumn);

        $skuColumn = $this->getColumnBaseData('sku', false, __('SKU'), 30);
        $skuColumn = $this->setColumnSpecialData($skuColumn);

        $priceColumn = $this->getColumnBaseData('price', false, __($this->getPriceLabel()), 40);
        $priceColumn = $this->setColumnSpecialData(
            $priceColumn,
            'TNW_Subscriptions/js/grid/billing_frequency/price',
            'TNW_Subscriptions/billing_frequency/input',
            [],
            $this->getCurrencySymbol(),
            'ea.',
            null,
            true,
            ['currencySymbol' => $this->getCurrencySymbol()]
        );

        $lockProductPriceColumn = $this->getColumnBaseData(
            LockPrice::CODE_LOCK_PRICE,
            false,
            __(LockPrice::CODE_LOCK_PRICE),
            50
        );
        $lockProductPriceColumn = $this->setColumnSpecialData(
            $lockProductPriceColumn,
            null,
            null,
            [],
            null,
            null,
            null,
            false
        );

        $codeFlatDiscountColumn = $this->getColumnBaseData(
            LockPrice::CODE_FLAT_DISCOUNT,
            false,
            __(LockPrice::CODE_FLAT_DISCOUNT),
            60
        );
        $codeFlatDiscountColumn = $this->setColumnSpecialData(
            $codeFlatDiscountColumn,
            null,
            null,
            [],
            null,
            null,
            null,
            false
        );

        $discountAmountColumn = $this->getColumnBaseData(
            Discount::CODE_DISCOUNT_AMOUNT,
            false,
            __( Discount::CODE_DISCOUNT_AMOUNT),
            70
        );
        $discountAmountColumn = $this->setColumnSpecialData(
            $discountAmountColumn,
            null,
            null,
            [],
            null,
            null,
            null,
            false
        );

        $discountTypeColumn = $this->getColumnBaseData(
            Discount::CODE_DISCOUNT_TYPE,
            true,
            __(Discount::CODE_DISCOUNT_TYPE),
            80
        );
        $discountTypeColumn = $this->setColumnSpecialData(
            $discountTypeColumn,
            null,
            null,
            [],
            null,
            null,
            null,
            false
        );

        $initialFeeColumn = $this->getColumnBaseData('initial_fee', false, __('Initial Fee'), 90);
        $initialFeeColumn = $this->setColumnSpecialData(
            $initialFeeColumn,
            null,
            null,
            [],
            null,
            null,
            __('Fee chanrged once upon creation of the subscription. Leave blank if subscription has no initial fee.')
        );

        $statusColumn = $this->getColumnBaseData('status', false, __('Status'), 100);
        $statusColumn = $this->setColumnSpecialData($statusColumn);

        $presetQtyColumn = $this->getColumnBaseData('preset_qty', false, __('Preset Qty'), 110);
        $presetQtyColumn = $this->setColumnSpecialData(
            $presetQtyColumn,
            'TNW_Subscriptions/js/grid/billing_frequency/preset_qty',
            ''
        );

        $unlockPresetQtyColumn = $this->getColumnBaseData(UnlockPresetQty::CODE_UNLOCK_PRESET_QTY,
            false,
            __(UnlockPresetQty::CODE_UNLOCK_PRESET_QTY),
            120
        );
        $unlockPresetQtyColumn = $this->setColumnSpecialData(
            $unlockPresetQtyColumn,
            null,
            null,
            [],
            null,
            null,
            null,
            false
        );

        $columns = [
            'id' => $idColumn,
            'image' => $imageColumn,
            'name' => $nameColumn,
            'sku' => $skuColumn,
            'price' => $priceColumn,
            'lock_price' => $lockProductPriceColumn,
            'flat_discount' => $codeFlatDiscountColumn,
            'discount_amount' => $discountAmountColumn,
            'discount_type' => $discountTypeColumn,
            'initial_fee' => $initialFeeColumn,
            'status' => $statusColumn,
            'preset_qty' => $presetQtyColumn,
            'unlock_preset_qty' => $unlockPresetQtyColumn,
        ];

        return $columns;
    }
}
