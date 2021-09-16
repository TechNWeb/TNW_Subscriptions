<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Linked;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Data provider of linked products grid
 */
class LinkedProductsProvider extends DataProvider
{
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        AttributeRepositoryInterface $attributeRepository,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->productMetadata = $productMetadata;
        $this->attributeRepository = $attributeRepository;
        $this->storeManager = $storeManager;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        return array_merge_recursive(
            $meta,
            [
                'bf_products_columns' => [
                    'children' => [
                        'price' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'addBefore' => $this->getCurrencySymbol()
                                    ]
                                ]
                            ]
                        ],
                        'initial_fee' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'addBefore' => $this->getCurrencySymbol()
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getSearchResult()
    {
        /** @var SearchResult $result */
        $result = parent::getSearchResult();
        if ($result->isLoaded()) {
            return $result;
        }
        if (!$this->request->getParam('billing_frequency_id')) {
            $result->getSelect()->where('id IS NULL');
            return $result;
        }

        $edition = $this->productMetadata->getEdition();

        $column = 'entity_id';
        // In case of EE we need to use row_id instead of entity_id
        if ($edition == 'Enterprise') {
            $column = 'row_id';
        }

        $result->getSelect()->joinLeft(
            ['product' => $result->getTable('catalog_product_entity')],
            'main_table.magento_product_id = product.entity_id',
            ['sku', 'entity_id']
        );

        $productAttributes = [
            'name' => 'product_name',
            'thumbnail' => 'thumbnail',
            'status' => 'status',
            'price' => 'original_price',
            Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY => 'unlock_preset_qty',
            Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE => 'lock_product_price'
        ];

        foreach ($productAttributes as $attributeName => $attributeAlias) {
            $attribute = $this->attributeRepository->get('catalog_product', $attributeName);
            $tableName = $attributeName . 'table';
            $result->getSelect()->joinLeft(
                [$tableName => $attribute->getBackendTable()],
                $tableName . '.' . $column . ' = product.' . $column
                . ' AND ' . $tableName . '.attribute_id =' . $attribute->getAttributeId(),
                [$attributeAlias => $tableName . '.value']
            );
        }

        return $result;
    }

    /**
     * Get currency symbol.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrencySymbol()
    {
        return $this->storeManager->getStore()->getBaseCurrency()->getCurrencySymbol();
    }
}
