<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\Report\CustomerBillingFrequency;

use Magento\Framework\Api;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use TNW\Subscriptions\Model\ResourceModel\BillingFrequency;

class Collection extends BillingFrequency\Collection implements Api\Search\SearchResultInterface
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
        $this->_init(Document::class, BillingFrequency::class);
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this
            ->join(
                ['subscription_profile'=>'tnw_subscriptions_subscription_profile_entity'],
                'billing_frequency_id=id',
                [
                    'customer_count' => 'COUNT(DISTINCT subscription_profile.customer_id)'
                ]
            )
            ->getSelect()->group(['id']);
    }

    /**
     * @inheritdoc
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
