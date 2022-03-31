<?php

/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ImportExport\Export;

use Magento\Catalog\Model\Product as ProductEntity;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\CatalogImportExport\Model\Export\Product\Type\Factory;
use Magento\CatalogImportExport\Model\Export\ProductFilterInterface;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\CatalogImportExport\Model\Export\Product as ExportProduct;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttrSetCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttrCollectionFactory;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class for adding billing frequency columns to csv file
 */
class Product extends ExportProduct
{
    /**
     * Attributes defined by user
     *
     * @var array
     */
    private $userDefinedAttributes = [];

    /**
     * @var ProductBillingFrequencyRepositoryInterface
     */
    private $billingFrequencyRepository;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * Product constructor.
     * @param TimezoneInterface $localeDate
     * @param Config $config
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param ProductCollectionFactory $collectionFactory
     * @param ConfigInterface $exportConfig
     * @param ProductFactory $productFactory
     * @param AttrSetCollectionFactory $attrSetColFactory
     * @param CategoryCollectionFactory $categoryColFactory
     * @param ItemFactory $itemFactory
     * @param CollectionFactory $optionColFactory
     * @param AttrCollectionFactory $attributeColFactory
     * @param Factory $_typeFactory
     * @param LinkTypeProvider $linkTypeProvider
     * @param RowCustomizerInterface $rowCustomizer
     * @param ProductBillingFrequencyRepositoryInterface $billingFrequencyRepository
     * @param Json $json
     * @param array $dateAttrCodes
     * @param ProductFilterInterface|null $filter
     * @throws LocalizedException
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Config $config,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ProductCollectionFactory $collectionFactory,
        ConfigInterface $exportConfig,
        ProductFactory $productFactory,
        AttrSetCollectionFactory $attrSetColFactory,
        CategoryCollectionFactory $categoryColFactory,
        ItemFactory $itemFactory,
        CollectionFactory $optionColFactory,
        AttrCollectionFactory $attributeColFactory,
        Factory  $_typeFactory,
        LinkTypeProvider $linkTypeProvider,
        RowCustomizerInterface $rowCustomizer,
        ProductBillingFrequencyRepositoryInterface $billingFrequencyRepository,
        Json $json,
        array $dateAttrCodes = [],
        ?ProductFilterInterface $filter = null
    ) {
        $this->_exportMainAttrCodes = array_merge($this->_exportMainAttrCodes, [
            'tnw_subscr_discount_amount',
            'tnw_subscr_discount_type',
            'tnw_subscr_hide_qty',
            'tnw_subscr_inf_subscriptions',
            'tnw_subscr_inheritance',
            'tnw_subscr_lock_product_price',
            'tnw_subscr_offer_flat_discount',
            'tnw_subscr_purchase_type',
            'tnw_subscr_savings_calculation',
            'tnw_subscr_start_date',
            'tnw_subscr_trial_length',
            'tnw_subscr_trial_length_unit',
            'tnw_subscr_trial_price',
            'tnw_subscr_trial_start_date',
            'tnw_subscr_unlock_preset_qty',
            'tnw_subscr_trial_status',
            'tnw_subscr_billing_frequency_used',
            'tnw_subscr_billing_frequency'
        ]);
        parent::__construct(
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $logger,
            $collectionFactory,
            $exportConfig,
            $productFactory,
            $attrSetColFactory,
            $categoryColFactory,
            $itemFactory,
            $optionColFactory,
            $attributeColFactory,
            $_typeFactory,
            $linkTypeProvider,
            $rowCustomizer,
            $dateAttrCodes,
            $filter
        );
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->json = $json;
    }

    /**
     * Collect export data for all products
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * phpcs:disable Generic.Metrics.NestingLevel
     * @throws \Exception
     */
    protected function collectRawData()
    {
        $data = [];
        $items = $this->loadCollection();

        /**
         * @var int $itemId
         * @var ProductEntity[] $itemByStore
         */
        foreach ($items as $itemId => $itemByStore) {
            $billingFrequency = null;
            foreach ($this->_storeIdToCode as $storeId => $storeCode) {
                $item = $itemByStore[$storeId];
                $additionalAttributes = [];
                $productLinkId = $item->getData($this->getProductEntityLinkField());
                foreach ($this->_getExportAttrCodes() as $code) {
                    $attrValue = $item->getData($code);
                    if (!$this->isValidAttributeValue($code, $attrValue)) {
                        continue;
                    }

                    if (isset($this->_attributeValues[$code][$attrValue]) && !empty($this->_attributeValues[$code])) {
                        $attrValue = $this->_attributeValues[$code][$attrValue];
                    }
                    $fieldName = isset($this->_fieldsMap[$code]) ? $this->_fieldsMap[$code] : $code;

                    if ($this->_attributeTypes[$code] == 'datetime') {
                        if (in_array($code, $this->dateAttrCodes)
                            || in_array($code, $this->userDefinedAttributes)
                        ) {
                            $attrValue = $this->_localeDate->formatDateTime(
                                new \DateTime($attrValue),
                                \IntlDateFormatter::SHORT,
                                \IntlDateFormatter::NONE,
                                null,
                                date_default_timezone_get()
                            );
                        } else {
                            $attrValue = $this->_localeDate->formatDateTime(
                                new \DateTime($attrValue),
                                \IntlDateFormatter::SHORT,
                                \IntlDateFormatter::SHORT
                            );
                        }
                    }

                    if ($storeId != Store::DEFAULT_STORE_ID
                        && isset($data[$itemId][Store::DEFAULT_STORE_ID][$fieldName])
                        && $data[$itemId][Store::DEFAULT_STORE_ID][$fieldName] == htmlspecialchars_decode($attrValue)
                    ) {
                        continue;
                    }

                    if ($this->_attributeTypes[$code] !== 'multiselect') {
                        if (is_scalar($attrValue)) {
                            if (!in_array($fieldName, $this->_getExportMainAttrCodes())) {
                                $additionalAttributes[$fieldName] = $fieldName .
                                    ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $this->wrapValue($attrValue);
                            }
                            $data[$itemId][$storeId][$fieldName] = htmlspecialchars_decode($attrValue);
                        }
                    } else {
                        $this->collectMultiselectValues($item, $code, $storeId);
                        if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId][$code])) {
                            $additionalAttributes[$code] = $fieldName .
                                ImportProduct::PAIR_NAME_VALUE_SEPARATOR . implode(
                                    ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR,
                                    $this->wrapValue($this->collectedMultiselectsData[$storeId][$productLinkId][$code])
                                );
                        }
                    }
                }

                if (!empty($additionalAttributes)) {
                    $additionalAttributes = array_map('htmlspecialchars_decode', $additionalAttributes);
                    $data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalAttributes);
                } else {
                    unset($data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES]);
                }

                $attrSetId = $item->getAttributeSetId();
                $data[$itemId][$storeId][self::COL_STORE] = $storeCode;
                $data[$itemId][$storeId][self::COL_ATTR_SET] = $this->_attrSetIdToName[$attrSetId];
                $data[$itemId][$storeId][self::COL_TYPE] = $item->getTypeId();
                $data[$itemId][$storeId][self::COL_SKU] = htmlspecialchars_decode($item->getSku());
                $data[$itemId][$storeId]['store_id'] = $storeId;
                $data[$itemId][$storeId]['product_id'] = $itemId;
                $data[$itemId][$storeId]['product_link_id'] = $productLinkId;
                $data[$itemId][$storeId]['tnw_subscr_billing_frequency_used'] = 0;
                $data[$itemId][$storeId]['tnw_subscr_billing_frequency'] =
                $this->getBillingFrequencyExportData($itemId);
            }
        }
        $result = [];
        foreach ($data as $row) {
            if (array_key_exists('tnw_subscr_billing_frequency_used', $row[1])) {
                unset($row[1]['tnw_subscr_billing_frequency_used']);
                unset($row[1]['tnw_subscr_billing_frequency']);
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Wrap values with double quotes if "Fields Enclosure" option is enabled
     *
     * @param string|array $value
     * @return string|array
     */
    private function wrapValue($value)
    {
        if (!empty($this->_parameters[\Magento\ImportExport\Model\Export::FIELDS_ENCLOSURE])) {
            $wrap = function ($value) {
                return sprintf('"%s"', str_replace('"', '""', $value));
            };

            $value = is_array($value) ? array_map($wrap, $value) : $wrap($value);
        }

        return $value;
    }

    /**
     * Get billing frequency for current product
     *
     * @param $itemId
     * @return string
     */
    protected function getBillingFrequencyExportData($itemId)
    {
        $result = [];
        try {
            foreach ($this->billingFrequencyRepository->getListByProductId($itemId)->getItems() as $item) {
                $data = $item->getData();
                unset($data['magento_product_id']);
                unset($data['id']);
                $result[] = $data;
            }
        } catch (LocalizedException $exception) {
            $this->_logger->error($exception->getMessage());
        }

        return $this->json->serialize($result);
    }
}
