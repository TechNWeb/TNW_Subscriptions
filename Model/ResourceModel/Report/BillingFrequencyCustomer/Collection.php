<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\Report\BillingFrequencyCustomer;

use Magento\Framework\Api;
use Magento\Customer\Model\ResourceModel\Customer;
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
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Document::class, Customer::class);
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
                [
                    'billing_frequency_id' => 'billing_frequency_id',
                    'billing_frequency_count'=>'COUNT(DISTINCT subscription_profile.billing_frequency_id)'
                ]
            )
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
     * @inheritdoc
     */
    public function setItems(?array $items = null)
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
     * @inheritdoc
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @inheritdoc
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * @inheritdoc
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTotalCount()
    {
        if (!$this->totalCount) {
            $this->totalCount = $this->getSize();
        }
        return $this->totalCount;
    }

    /**
     * @inheritdoc
     */
    public function setTotalCount($totalCount)
    {
        $this->totalCount = $totalCount;
        return $this;
    }
}
