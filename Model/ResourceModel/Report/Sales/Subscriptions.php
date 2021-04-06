<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\Report\Sales;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Api\Search\DocumentInterfaceFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\DataObjectFactory;

/**
 * Class Subscriptions - used as custom collection for report dataProvider
 */
class Subscriptions extends Collection implements SearchResultInterface
{
    /**
     * @var
     */
    protected $aggregations;

    /**
     * @var
     */
    protected $searchCriteria;

    /**
     * @var
     */
    protected $totalCount;

    /**
     * @var string
     */
    protected $document = Document::class;

    /**
     * @var ResourceConnection
     */
    protected $connection;

    /**
     * @var DocumentInterfaceFactory
     */
    protected $itemFactory;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * Subscriptions constructor.
     * @param EntityFactoryInterface $entityFactory
     * @param ResourceConnection $connection
     * @param DocumentInterfaceFactory $itemFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        ResourceConnection $connection,
        DocumentInterfaceFactory $itemFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        parent::__construct($entityFactory);
        $this->connection = $connection;
        $this->itemFactory = $itemFactory;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @return \Magento\Framework\Api\Search\DocumentInterface[]|\Magento\Framework\DataObject[]
     */
    public function getItems()
    {
        $this->load();
        return $this->_items;
    }

    /**
     * @param array|null $items
     * @return $this|SearchResultInterface
     */
    public function setItems(array $items = null)
    {
        if ($items) {
            foreach ($items as $item) {
                $this->_items[] = $item;
            }
            unset($this->totalCount);
        }

        return $this;
    }

    /**
     * @return \Magento\Framework\Api\Search\AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param \Magento\Framework\Api\Search\AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\Search\SearchCriteriaInterface
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        if (!$this->totalCount) {
            $this->totalCount = count($this->_items);
        }
        return $this->totalCount;
    }

    /**
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        $this->totalCount = $totalCount;
        return $this;
    }

    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     */
    public function load($printQuery = false, $logQuery = false)
    {
        return $this->loadData($printQuery, $logQuery);
    }

    /**
     * @param array|string $field
     * @param array|int|string $condition
     * @return Collection|void
     */
    public function addFieldToFilter($field, $condition)
    {
        $this->_filters[$field] = $this->dataObjectFactory->create(['data' => [$field => $condition]]);
    }

    /**
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this|Collection
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if (!$this->_items && !$this->isLoaded() && $this->_filters) {
            foreach ($this->_filters as $filterName => $filter) {
                switch ($filterName) {
                    case 'from': $fromDate = $filter->getData($filterName)['eq']; break;
                    case 'to': $toDate = $filter->getData($filterName)['eq']; break;
                    case 'date': $date = $filter->getData($filterName)['eq']; break;
                    case 'order_status': $status = $filter->getData($filterName)['eq'];break;
                    default: //TODO: make possibility to apply other filters to collection
                        break;
                }
            }
            if (isset($date, $fromDate, $toDate, $status)) {
                $select = $this->connection->getConnection()->select()
                    ->from(
                        $this->connection
                            ->getConnection()
                            ->getTableName('tnw_subscriptions_subscription_profile_order'),
                        'magento_order_id'
                    )->joinLeft(
                        [
                            'order_item' => $this->connection
                                ->getConnection()
                                ->getTableName('sales_order_item')
                        ],
                        'magento_order_id = order_item.order_id',
                        [
                            'qty_ordered', 'sku', 'name', 'base_row_total_incl_tax'
                        ]
                    )->joinLeft(
                        [
                            'quote_item_option' => $this->connection
                                ->getConnection()
                                ->getTableName('quote_item_option')
                        ],
                        'order_item.quote_item_id = quote_item_option.item_id',
                        [
                            'value'
                        ]
                    )->joinLeft(
                        [
                            'order' => $this->connection->getConnection()->getTableName('sales_order_grid')
                        ],
                        'magento_order_id = order.entity_id',
                        [
                            'status', $date
                        ]
                    )->where(
                        $this->connection->getConnection()->prepareSqlCondition(
                            'order.' . $date,
                            array(
                                "from" => date('Y-m-d 00:00:00', strtotime($fromDate)),
                                "to" => date('Y-m-d 23:59:59', strtotime($toDate))
                            )
                        )
                    )->where(
                        $this->connection->getConnection()->prepareSqlCondition(
                            'order.status',
                            $status
                        )
                    )->where(
                        $this->connection->getConnection()->prepareSqlCondition(
                            'quote_item_option.code',
                            'subscription'
                        )
                    )->order('order.' . $date . ' ' . Select::SQL_ASC);

                foreach ($this->connection->getConnection()->fetchAll($select) as $row) {
                    $this->_items[] = $this->itemFactory->create(['data' => $row]);
                }
                $this->_setIsLoaded(true);
            }
        }
        return $this;
    }

    /**
     * Retrieve array of attributes
     *
     * @param array $arrAttributes
     * @return array
     */
    public function toArray($arrAttributes = [])
    {
        $arr = [];
        foreach ($this->getItems() as $key => $item) {
            $arr[$key] = $item->__toArray();
        }
        return $arr;
    }
}
