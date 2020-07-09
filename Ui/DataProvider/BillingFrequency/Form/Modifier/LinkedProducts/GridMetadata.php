<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Form\Modifier\LinkedProducts;

use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\UrlFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\Product\Attribute;

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
     * URL instance
     *
     * @var UrlFactory
     */
    protected $urlFactory;

    /**
     * @param BillingFrequencyUnitType $billingFrequencyUnitType
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param UrlFactory $urlFactory
     */
    public function __construct(
        BillingFrequencyUnitType $billingFrequencyUnitType,
        StoreManagerInterface $storeManager,
        Registry $registry,
        UrlFactory $urlFactory
    ) {
        $this->billingFrequencyUnitType = $billingFrequencyUnitType;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->urlFactory = $urlFactory;
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
            $periodLabel = $this->billingFrequencyUnitType->getPeriodLabel(
                $frequency->getUnit(),
                $frequency->getFrequency()
            );

            $priceLabel = $periodLabel . ' ' . $priceLabel;
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
     * @param array $validate
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
        $imports = [],
        $validate = []
    ) {
        $component ? $column['arguments']['data']['config']['component'] = $component : null;
        $elementTmpl ? $column['arguments']['data']['config']['elementTmpl'] = $elementTmpl : null;
        $service ? $column['arguments']['data']['config']['service'] = $service : null;
        $addBefore ? $column['arguments']['data']['config']['addbefore'] = $addBefore : null;
        $addAfter ? $column['arguments']['data']['config']['addafter'] = $addAfter : null;
        $notice ? $column['arguments']['data']['config']['notice'] = $notice : null;
        $visible ?: $column['arguments']['data']['config']['visible'] = $visible;
        $imports ? $column['arguments']['data']['config']['imports'] = $imports : null;
        $validate ? $column['arguments']['data']['config']['validation'] = $validate : null;

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
            'TNW_Subscriptions/js/grid/action-a-href',
            'TNW_Subscriptions/grid/cells/thumbnail-a-href',
            [],
            null,
            null,
            null,
            true,
            ['url' => $this->getProductUrl()]
        );

        $nameColumn = $this->getColumnBaseData('name', false, __('Name'), 20);
        $nameColumn = $this->setColumnSpecialData(
            $nameColumn,
            'TNW_Subscriptions/js/grid/action-a-href',
            'TNW_Subscriptions/grid/cells/action-a-href',
            [],
            null,
            null,
            null,
            true,
            ['url' => $this->getProductUrl()]
        );

        $skuColumn = $this->getColumnBaseData('sku', false, __('SKU'), 30);
        $skuColumn = $this->setColumnSpecialData($skuColumn);
        $isActiveColumn = $this->getColumnBaseData('is_active', false, __('Is Active'), 120);
        $isActiveColumn = $this->setColumnSpecialData($isActiveColumn);

        $priceColumn = $this->getColumnBaseData('price', false, __($this->getPriceLabel()), 40);
        $priceColumn = $this->setColumnSpecialData(
            $priceColumn,
            'TNW_Subscriptions/js/grid/billing_frequency/price',
            'TNW_Subscriptions/grid/cells/price',
            [],
            $this->getCurrencySymbol(),
            null,
            null,
            true,
            [
                'currencySymbol' => $this->getCurrencySymbol(),
                'customAddAfter' => __('ea.'),
            ],
            [
                'validate-zero-or-greater' => true,
            ]
        );

        $priceColumn['arguments']['data']['config']['columnsHeaderClasses'] = 'tnw_price_column';

        $lockProductPriceColumn = $this->getColumnBaseData(
            Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE,
            false,
            __(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE),
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
            Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT,
            false,
            __(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT),
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
            Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT,
            false,
            __(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT),
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
            Attribute::SUBSCRIPTION_DISCOUNT_TYPE,
            true,
            __(Attribute::SUBSCRIPTION_DISCOUNT_TYPE),
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
            __('Fee charged once upon creation of the subscription. Leave blank if subscription has no initial fee.'),
            true,
            [],
            [
                'validate-zero-or-greater' => true,
            ]
        );

        $initialFeeColumn['arguments']['data']['config']['columnsHeaderClasses'] = 'tnw_price_column';

        $regPriceColumn = $this->getColumnBaseData('reg_price', false, __('Reg. Price'), 95);
        $regPriceColumn = $this->setColumnSpecialData($regPriceColumn);

        $statusColumn = $this->getColumnBaseData('status', false, __('Status'), 100);
        $statusColumn = $this->setColumnSpecialData($statusColumn);

        $presetQtyColumn = $this->getColumnBaseData('preset_qty', false, __('Preset Qty'), 110);
        $presetQtyColumn = $this->setColumnSpecialData(
            $presetQtyColumn,
            'TNW_Subscriptions/js/grid/billing_frequency/preset_qty',
            ''
        );

        $unlockPresetQtyColumn = $this->getColumnBaseData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
            false,
            __(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY),
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
            'reg_price' => $regPriceColumn,
            'status' => $statusColumn,
            'preset_qty' => $presetQtyColumn,
            'unlock_preset_qty' => $unlockPresetQtyColumn,
            'is_active' => $isActiveColumn,
        ];

        return $columns;
    }

    /**
     * Get url for product edit page.
     *
     * @return string
     */
    private function getProductUrl()
    {
        $url = $this->urlFactory->create();

        return $url->getUrl('catalog/product/edit', ['id' => 'linked_id']);
    }
}
