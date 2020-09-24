<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\Report\ProductCustomer;

use Magento\Framework\Api;
use \Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;

class Collection extends Customer\Collection implements Api\Search\SearchResultInterface
{
    /**
     * @var Api\Search\AggregationInterface
     */
    protected $aggregations;

    /**
     * @var Api\Search\SearchCriteriaInterface
     */
    protected $searchCriteria;

    /**
     * @var int
     */
    protected $totalCount;

    /**
     * @var string class name of document
     */
    protected $document = Document::class;

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init($this->document, \Magento\Customer\Model\ResourceModel\Customer::class);
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this
            ->addExpressionAttributeToSelect('id_field_name', new \Zend_Db_Expr("'entity_id'"), [])
            ->addNameToSelect()
            ->joinTable(
                ['subscription_profile'=>'tnw_subscriptions_subscription_profile_entity'],
                'customer_id=entity_id',
                ['subscription_profile_id' => 'entity_id']
            )
            ->joinTable(
                ['product_subscription_profile'=>'tnw_subscriptions_product_subscription_profile_entity'],
                'subscription_profile_id=subscription_profile_id',
                [
                    'product_id' => 'magento_product_id',
                    'product_parent_id' => 'parent_id',
                    'product_count'=>'COUNT(DISTINCT product_subscription_profile.magento_product_id)'
                ]
            )
            ->addAttributeToFilter([
                ['attribute'=>'product_parent_id', 'null'=>true]
            ])
            ->groupByAttribute(['entity_id']);
    }

    /**
     * @inheritdoc
     */
    protected function beforeAddLoadedItem(\Magento\Framework\DataObject $item)
    {
        return $item;
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad()
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\Search\DocumentInterface[] $items
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setItems(array $items = null)
    {
        if ($items) {
            foreach ($items as $item) {
                $this->addItem($item);
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
            $this->totalCount = $this->getSize();
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
}
