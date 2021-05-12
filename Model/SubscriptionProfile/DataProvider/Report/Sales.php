<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Report;

use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Model\ResourceModel\Report\Sales\SubscriptionsFactory;
use Magento\Framework\App\RequestInterface;
use TNW\Subscriptions\Model\ResourceModel\Report\Sales\Subscriptions;

/**
 * Class Sales - dataProvider for sales report
 */
class Sales extends AbstractDataProvider
{
    /**
     * Data Provider name
     *
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $orders = [];

    /**
     * Data Provider Primary Identifier name
     *
     * @var string
     */
    protected $primaryFieldName;

    /**
     * Data Provider Request Parameter Identifier name
     *
     * @var string
     */
    protected $requestFieldName;

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * Provider configuration data
     *
     * @var array
     */
    protected $data = [];

    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\Report\Sales\Subscriptions
     */
    protected $collection;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Sales constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param SubscriptionsFactory $collectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        SubscriptionsFactory $collectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->request = $request;
        $this->collection = $collectionFactory->create();
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
     * @return Subscriptions
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Get Data Provider name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get primary field name
     *
     * @return string
     */
    public function getPrimaryFieldName()
    {
        return $this->primaryFieldName;
    }

    /**
     * Get field name in request
     *
     * @return string
     */
    public function getRequestFieldName()
    {
        return $this->requestFieldName;
    }

    /**
     * Return Meta
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Get field Set meta info
     *
     * @param string $fieldSetName
     * @return array
     */
    public function getFieldSetMetaInfo($fieldSetName)
    {
        return $this->meta[$fieldSetName] ?? [];
    }

    /**
     * Return fields meta info
     *
     * @param string $fieldSetName
     * @return array
     */
    public function getFieldsMetaInfo($fieldSetName)
    {
        return $this->meta[$fieldSetName]['children'] ?? [];
    }

    /**
     * Return field meta info
     *
     * @param string $fieldSetName
     * @param string $fieldName
     * @return array
     */
    public function getFieldMetaInfo($fieldSetName, $fieldName)
    {
        return $this->meta[$fieldSetName]['children'][$fieldName] ?? [];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        $this->getCollection()->addFieldToFilter(
            $filter->getField(),
            [$filter->getConditionType() => $filter->getValue()]
        );
    }

    /**
     * @return null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @return Subscriptions
     */
    public function getSearchResult()
    {
        return $this->getCollection();
    }

    /**
     * Alias for self::setOrder()
     *
     * @param string $field
     * @param string $direction
     * @return void
     */
    public function addOrder($field, $direction)
    {
        $this->orders[] = [$field => $direction];
    }

    /**
     * Set Query limit
     *
     * @param int $offset
     * @param int $size
     * @return void
     */
    public function setLimit($offset, $size)
    {
        $this->getCollection()->setPageSize($size);
        $this->getCollection()->setCurPage($offset);
    }

    /**
     * Removes field from select
     *
     * @param string|null $field
     * @param bool $isAlias Alias identifier
     * @return void
     */
    public function removeField($field, $isAlias = false)
    {
        $this->getCollection()->removeFieldFromSelect($field, $isAlias);
    }

    /**
     * Removes all fields from select
     *
     * @return void
     */
    public function removeAllFields()
    {
        $this->getCollection()->removeAllFieldsFromSelect();
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
        $unProcessedItems = $this->getCollection()->toArray();
        $data = [];
        if ($unProcessedItems && $date && $period) {
            $processedItemsGroupedByPeriods = [];
            $periodStartTime = '';
            $periodEndingTime = '';
            $periodGroupValue = '';
            foreach ($unProcessedItems as $itemData) {
                $itemDateTime = strtotime($itemData[$date]);
                if (!$periodStartTime || !$periodEndingTime || $itemDateTime > strtotime($periodEndingTime)) {
                    switch ($period) {
                        case 'day':
                            $periodStartTime = date("Y-m-d 00:00:00", $itemDateTime);
                            $periodEndingTime = date("Y-m-d 23:59:59", $itemDateTime);
                            break;
                        case 'month':
                            $periodStartTime = date("Y-m-1 00:00:00", $itemDateTime);
                            $periodEndingTime = date("Y-m-t 23:59:59", $itemDateTime);
                            break;
                        default:
                            $periodStartTime = date("Y-1-1 00:00:00", $itemDateTime);
                            $periodEndingTime = date("Y-12-31 23:59:59", $itemDateTime);
                            break;
                    }
                    $periodGroupValue = $periodEndingTime;
                }
                if (array_key_exists($periodGroupValue, $processedItemsGroupedByPeriods)
                    && array_key_exists($itemData['sku'], $processedItemsGroupedByPeriods[$periodGroupValue])
                ) {
                    $processedItemsGroupedByPeriods[$periodGroupValue][$itemData['sku']]['total']
                        += $itemData['base_row_total_incl_tax'];
                    $processedItemsGroupedByPeriods[$periodGroupValue][$itemData['sku']]['qty']
                        += $itemData['qty_ordered'];
                } else {
                    $resultedRow = [
                        'total' => $itemData['base_row_total_incl_tax'],
                        'sku' => $itemData['sku'],
                        'name' => $itemData['name'],
                        'qty' => $itemData['qty_ordered'],
                        'interval' => $periodGroupValue
                    ];
                    $processedItemsGroupedByPeriods[$periodGroupValue][$itemData['sku']] = $resultedRow;
                }
            }
            foreach ($processedItemsGroupedByPeriods as $periodValue => $skuBasedData) {
                foreach ($skuBasedData as $sku => $rowData) {
                    $data[] = $rowData;
                }
            }
        }
        //TODO: implement sort order based on grid selected order
        return [
            'totalRecords' => count($data),
            'items' => $data
        ];
    }

    /**
     * Retrieve count of loaded items
     *
     * @return int
     */
    public function count()
    {
        return $this->getCollection()->count();
    }

    /**
     * Get config data
     *
     * @return mixed
     */
    public function getConfigData()
    {
        return $this->data['config'] ?? [];
    }

    /**
     * Set data
     *
     * @param mixed $config
     * @return void
     */
    public function setConfigData($config)
    {
        $this->data['config'] = $config;
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
            $this->getCollection()->addFieldToFilter($paramName, ['eq' => $paramValue]);
        }
    }
}
