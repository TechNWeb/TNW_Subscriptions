<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Report;

use Magento\Sales\Model\Order;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\RequestInterface;
use TNW\Subscriptions\Model\ResourceModel\Report\Subscriptions\Collection as CreatedAtCollection;
use TNW\Subscriptions\Model\ResourceModel\Report\Subscriptions\UpdatedAt\Collection as UpdatedAtCollection;
use Magento\Reports\Model\ResourceModel\Report\Collection\Factory as CollectionFactory;
use Magento\Sales\Model\Order\ConfigFactory;

/**
 * Class Sales - dataProvider for sales report
 */
class Sales extends AbstractDataProvider
{
    /**
     * @var CreatedAtCollection|UpdatedAtCollection
     */
    protected $collection;

    /**
     * @var CreatedAtCollection|UpdatedAtCollection
     */
    protected $totalsCollection;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    private $loadedData = [];

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * Sales constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param ConfigFactory $configFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        ConfigFactory $configFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->request = $request;
        $this->collectionFactory = $collectionFactory;
        $this->configFactory = $configFactory;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
        $this->prepareUpdateUrl();
    }

    /**
     * @return CreatedAtCollection|UpdatedAtCollection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->prepareCollection($this->createCollection());
        }

        return $this->collection;
    }

    /**
     * @return CreatedAtCollection|UpdatedAtCollection
     */
    public function getTotalsCollection()
    {
        if (!$this->totalsCollection) {
            $collection = $this->prepareCollection($this->createCollection());
            $collection->isTotals(true);
            $this->totalsCollection = $collection;
        }

        return $this->totalsCollection;
    }

    /**
     * @return CreatedAtCollection|UpdatedAtCollection
     */
    public function getSearchResult()
    {
        return $this->getCollection();
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $date = $this->request->getParam('date');
        $period = $this->request->getParam('period');
        $from = $this->request->getParam('from');
        $to = $this->request->getParam('to');

        if (!$date || !$period || ! $from || !$to) {
            return [];
        }

        $params =  $this->request->getParams();
        unset($params['sorting']);
        $dataKey = implode(',', $params);

        if (!array_key_exists($dataKey, $this->loadedData)) {
            $this->getCollection()->load();
            $this->addGrowthDataToCollection();
            $data = $this->getCollection()->toArray();
            if (!empty($data['items'])) {
                $totals = $this->getTotalsCollection()->load()->toArray();
                $totalsRow = reset($totals['items']);
                $totalsRow['period'] = 'Total';
                $data['totals'] = $totalsRow;
            }
            $this->loadedData[$dataKey] = $data;
        }

        return $this->loadedData[$dataKey];
    }

    /**
     * @param CreatedAtCollection|UpdatedAtCollection $collection
     * @return CreatedAtCollection|UpdatedAtCollection
     */
    protected function prepareCollection($collection)
    {
        $collection->setPeriod(
            $this->request->getParam('period')
        );

        if ($from = $this->request->getParam('from')) {
            $from = date('Y-m-d 00:00:00', strtotime($from));
        }

        if ($to = $this->request->getParam('to')) {
            $to = date('Y-m-d 23:59:59', strtotime($to));
        }

        $collection->setDateRange($from, $to);

        $statusFilter = $this->request->getParam('order_status');
        if (!$statusFilter || $statusFilter === 'any') {
            $orderConfig = $this->configFactory->create();
            $statusValues = [];
            $canceledStatuses = $orderConfig->getStateStatuses(Order::STATE_CANCELED);
            $statusCodes = array_keys($orderConfig->getStatuses());
            foreach ($statusCodes as $code) {
                if (!isset($canceledStatuses[$code])) {
                    $statusValues[] = $code;
                }
            }
            $collection->addOrderStatusFilter($statusValues);
        } else {
            $collection->addOrderStatusFilter($statusFilter);
        }

        // TODO: add store ids filter

        return $collection;
    }

    /**
     * @return void
     */
    protected function prepareUpdateUrl()
    {
        if (!isset($this->data['config']['filter_url_params'])) {
            return;
        }
        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' == $paramValue) {
                $paramValue = $this->request->getParam($paramName);
            }
            if ($paramValue) {
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s/',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue
                );
            }
        }
    }

    /**
     * @return string
     */
    protected function getResourceCollectionName()
    {
        return $this->request->getParam('date') === 'updated_at' ? UpdatedAtCollection::class : CreatedAtCollection::class;
    }

    /**
     * @return CreatedAtCollection|UpdatedAtCollection
     */
    protected function createCollection()
    {
        return $this->collectionFactory->create($this->getResourceCollectionName());
    }

    /**
     * Adds growth data to collection.
     *
     * @return void
     */
    private function addGrowthDataToCollection()
    {
        $columns = [
            'orders_count',
            'total_qty_ordered',
            'total_qty_invoiced',
            'total_income_amount',
            'total_revenue_amount',
            'total_profit_amount',
            'total_invoiced_amount',
            'total_canceled_amount',
            'total_paid_amount',
            'total_refunded_amount',
            'total_tax_amount',
            'total_tax_amount_actual',
            'total_shipping_amount',
            'total_shipping_amount_actual'
        ];

        $prevItem = null;

        foreach ($this->getCollection() as $item) {
            if ($prevItem) {
                foreach ($columns as $column) {
                    $growthColumn = $column . '_growth';
                    $item->setData(
                        $growthColumn,
                        $this->getGrowth(
                            (float) $prevItem->getData($column),
                            (float) $item->getData($column)
                        )
                    );
                }
            } else {
                foreach ($columns as $column) {
                    $growthColumn = $column . '_growth';
                    $item->setData($growthColumn, null);
                }
            }

            $prevItem = $item;
        }
    }

    /**
     * Returns term's growth, based on previous value.
     *
     * @param float|int $previous
     * @param float|int $current
     * @return float|int
     */
    private function getGrowth($previous, $current)
    {
        return $previous ? ($current - $previous) / abs($previous) : null;
    }
}
