<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as Resource;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class Grid Collection
 */
class Collection extends SearchResult
{
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
        parent::__construct($entityFactory, $logger, $fetchStrategy,
            $eventManager, $mainTable, $resourceModel
        );
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
        $columns = [
            'entity_id' => 'main_table.entity_id',
            'label' => $connection->getConcatSql(
                [
                    $connection->quote(SubscriptionProfileInterface::LABEL_PREFIX),
                    'main_table.entity_id'
                ]
            ),
            'customer_name' => 'customer.name',
            'customer_email' => 'customer.email',
            'frequency_label' => 'frequency.label',
            'website_id' => 'main_table.website_id',
            'status' => 'main_table.status',
            'trial_start_date' => 'main_table.trial_start_date',
            'start_date' => 'main_table.start_date',
            'next_billing_cycle_date' => new \Zend_Db_Expr('MIN(relation.scheduled_at)')
        ];
        $this->getSelect()->join(
                ['frequency' => BillingFrequencyInterface::SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE],
                'main_table.billing_frequency_id = frequency.id'
            )->join(
                ['relation' => SubscriptionProfileOrderInterface::MAIN_TABLE],
                'main_table.entity_id = relation.subscription_profile_id'
            )->join(
                ['customer' => $connection->getTableName('customer_grid_flat')],
                'customer.entity_id = main_table.customer_id'
            )->reset(
                \Zend_Db_Select::COLUMNS
            )->columns(
                $columns
            )->where(
                'relation.magento_order_id IS NULL'
            )->group(
                ['main_table.entity_id']
            );

        return $this;
    }
}
