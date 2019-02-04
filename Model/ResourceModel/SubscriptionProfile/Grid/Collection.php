<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Grid;

use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class Grid Collection
 */
class Collection extends \Magento\Eav\Model\Entity\Collection\AbstractCollection implements \Magento\Framework\Api\Search\SearchResultInterface
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
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \TNW\Subscriptions\Model\SubscriptionProfile::class,
            \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile::class
        );
    }

    /**
     * @return $this|\Magento\Eav\Model\Entity\Collection\AbstractCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $connection = $this->getConnection();

        $this->addAttributeToSelect('grand_total');

        $this->getSelect()
            ->joinLeft(
                ['frequency' => $this->getTable(BillingFrequencyInterface::SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE)],
                'billing_frequency_id = frequency.id',
                ['frequency_label' => 'frequency.label']
            )
            ->joinLeft(
                ['relation' => $this->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
                'relation.id = (' . (string)$this->getRelationJoinSelect(). ')',
                ['next_billing_cycle_date' => 'relation.scheduled_at']
            )
            ->joinLeft(
                ['customer' => $this->getTable('customer_entity')],
                'customer_id = customer.entity_id',
                [
                    'customer_name' => $connection->getConcatSql(
                        [
                            'customer.firstname',
                            'customer.lastname',
                        ],
                        ' '
                    ),
                    'customer_email' => 'customer.email',
                ]
            )
            ->joinLeft(
                ['payment' => $this->getTable(SubscriptionProfilePaymentInterface::SUBSCRIPTIONS_PROFILE_PAYMENT_TABLE)],
                'e.entity_id = payment.subscription_profile_id',
                [
                    'engine_code' => 'payment.engine_code',
                    'payment_additional_info' => 'payment.payment_additional_info',
                ]
            );

        return $this;
    }

    /**
     * Returns select
     *
     * @return \Magento\Framework\DB\Select
     */
    private function getRelationJoinSelect()
    {
        $result = $this->getConnection()->select();
        $result->from(
            [$this->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
            [SubscriptionProfileOrderInterface::ID]
        )->where(
            'e.entity_id=' . SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID
        )->where(
            SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID . ' IS NULL'
        )->where(
            'e.status not in (?)', [
                ProfileStatus::STATUS_COMPLETE,
                ProfileStatus::STATUS_CANCELED,
            ]
        )->order(
            SubscriptionProfileOrderInterface::SCHEDULED_AT . ' ASC'
        )->limit(1);

        return $result;
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
