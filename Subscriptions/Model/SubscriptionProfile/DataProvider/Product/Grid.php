<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Data provider for "Add product" modal product grid.
 */
class Grid extends ProductDataProvider
{
    /**#@+
     * Modal products grid data scope
     */
    const DATA_SCOPE_ADD_PRODUCT_GRID = 'tnw_subscriptionprofile_create_add_product_modal_listing';
    /**#@-*/

    /**
     * Flag shows if collection was already formed or it is necessary to join additional data to collection.
     *
     * @var bool
     */
    private $formedCollection = false;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaFactory;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param array $addFieldStrategies
     * @param array $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        StockItemRepositoryInterface $stockItemRepository,
        $addFieldStrategies = [],
        $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $collectionFactory,
            $addFieldStrategies, $addFilterStrategies, $meta, $data
        );
        $this->addField('tnw_subscr_unlock_preset_qty');
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
        $this->stockItemRepository = $stockItemRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $collection = $this->getCurrentCollection();
        $items = $collection->toArray();

        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items),
        ];
    }

    /**
     * Joins additional data to collection.
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    private function getCurrentCollection()
    {
        if (!$this->getCollection()->isLoaded() && !$this->formedCollection) {
            $this->getCollection()
                ->getSelect()
                ->join(
                    ['sub_table' =>
                        $this->getCollection()->getTable(ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE)],
                    'e.entity_id = sub_table.' . ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
                    []
                )
                ->join(
                    ['sub_frequency_table' =>
                        $this->getCollection()->getTable(BillingFrequencyInterface::SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE)],
                    'sub_frequency_table.' . BillingFrequencyInterface::ID.' = sub_table.'
                    . ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID
                    . ' AND sub_frequency_table.'. BillingFrequencyInterface::STATUS .' = 1',
                    []
                )
                ->group('e.entity_id');

            $this->formedCollection = true;
        }

        return $this->getCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            $this->getMetaData()
        );

        return $meta;
    }

    /**
     * Returns grid additional meta data.
     *
     * @return array
     */
    private function getMetaData()
    {
        $warningMessages = [
            'not_in_stock' => 'Not enough qty to sell.',
            'not_min_sale' => 'The fewest you may purchase is %1.',
            'not_max_sale' => 'The most you may purchase is %1.',
            'too_much' => 'We don\'t have as many "%1" as you requested.',
        ];

        /** @var array $metaData */
        $metaData = [
            'tnw_subscriptionprofile_product_columns' => [
                'children' => [
                    'qty' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'stockData' => $this->getStockData(),
                                    'warningMessages' => $warningMessages,
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $metaData;
    }

    /**
     * Returns products stock data to calculate the warning display on grid.
     *
     * @return array
     */
    private function getStockData()
    {
        $stockData = [];
        $stockItems = $this->getStockItems();

        /** @var \Magento\CatalogInventory\Model\Adminhtml\Stock\Item $stockItem */
        foreach ($stockItems as $stockItem) {
            $productId = (int)$stockItem->getProductId();
            $stockData[$productId] = [
                'qty' => $stockItem->getQty(),
                'is_in_stock' => $stockItem->getIsInStock(),
                'min_sale_qty' => $stockItem->getMinSaleQty(),
                'max_sale_qty' => $stockItem->getMaxSaleQty(),
                'manage_stock' => $stockItem->getManageStock(),
                'min_qty' => $stockItem->getMinQty(),
                'backorders' => $stockItem->getBackorders(),
            ];
        }

        return $stockData;
    }

    /**
     * Returns stock product items.
     *
     * @return array
     */
    private function getStockItems()
    {
        $collection = $this->getCurrentCollection();
        $productIds = $collection->getAllIds();
        if (empty($productIds)) {
            return [];
        }

        /** @var \Magento\CatalogInventory\Api\StockItemCriteriaInterface $criteria */
        $criteria = $this->stockItemCriteriaFactory->create();

        //$productIds was set to array because of Magento bug in \Magento\Framework\DB\AbstractMapper::map()
        $criteria->setProductsFilter([$productIds]);
        $stockItemsCollection = $this->stockItemRepository->getList($criteria);
        $stockItems = $stockItemsCollection->getItems();

        return $stockItems;
    }
}
