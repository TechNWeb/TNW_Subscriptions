<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as Resource;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Class Grid Collection
 */
class Collection extends SearchResult
{
    /**
     * @var string class name of document
     */
    protected $document = SubscriptionProfile::class;

    /**
     * Collection constructor.
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
        $resourceModel = Resource::class
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel
        );
    }

    /**
     * @inheritdoc
     */
    protected function _initInitialFieldsToSelect()
    {
        parent::_initInitialFieldsToSelect();

        $this->_initialFieldsToSelect = array_merge(
            $this->_initialFieldsToSelect,
            [
                'website_id',
                'status',
                'trial_start_date',
                'start_date',
                'created_at',
            ]
        );

        return $this;
    }

    /**
     * Init collection select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $connection = $this->getConnection();

        $this->addFieldToSelect(
            [
                'label' => $connection->getConcatSql(
                    [
                        $connection->quote(SubscriptionProfileInterface::LABEL_PREFIX),
                        'main_table.entity_id',
                    ]
                ),
            ]
        );

        $this->getSelect()->join(
            ['frequency' =>
                $this->getTable(BillingFrequencyInterface::SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE)],
            'main_table.billing_frequency_id = frequency.id',
            ['frequency_label' => 'frequency.label']
        )->joinLeft(
            ['relation' => $this->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
            'relation.id = (' . (string)$this->getRelationJoinSelect(). ')',
            ['next_billing_cycle_date' => 'relation.scheduled_at']
        )->joinLeft(
            ['quotes' => $this->getTable('quote')],
            'quotes.entity_id = relation.magento_quote_id',
            ['grand_total' => 'quotes.grand_total']
        )->join(
            ['customer' => $this->getTable('customer_entity')],
            'customer.entity_id = main_table.customer_id',
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
        )->join(
            ['payment' => $this->getTable(SubscriptionProfilePaymentInterface::SUBSCRIPTIONS_PROFILE_PAYMENT_TABLE)],
            'main_table.entity_id = payment.subscription_profile_id',
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
            'main_table.entity_id=' . SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID
        )->where(
            SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID . ' IS NULL'
        )->where(
            'main_table.status not in (?)', [
                ProfileStatus::STATUS_COMPLETE,
                ProfileStatus::STATUS_CANCELED,
            ]
        )->order(
            SubscriptionProfileOrderInterface::SCHEDULED_AT . ' ASC'
        )->limit(1);

        return $result;
    }
}
