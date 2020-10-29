<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
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
     * Collection constructor.
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @throws \Magento\Framework\Exception\LocalizedException
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
                'customer_id'
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

        $this->addFieldToSelect('grand_total');

        $this->getSelect()
            ->joinLeft(
                ['frequency' => $this->getTable(BillingFrequencyInterface::SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE)],
                'main_table.billing_frequency_id = frequency.id',
                ['frequency_label' => 'frequency.label']
            )
            ->joinLeft(
                ['relation' => $this->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
                'relation.id = (' . (string) $this->getRelationJoinSelect() . ')',
                ['next_billing_cycle_date' => 'relation.scheduled_at']
            )
            ->joinLeft(
                ['customer' => $this->getTable('customer_grid_flat')],
                'customer.entity_id = main_table.customer_id',
                [
                    'customer_name' => 'customer.name',
                    'customer_email' => 'customer.email',
                ]
            )
            ->joinLeft(
                ['payment' => $this->getTable(
                    SubscriptionProfilePaymentInterface::SUBSCRIPTIONS_PROFILE_PAYMENT_TABLE
                )],
                'main_table.entity_id = payment.subscription_profile_id',
                [
                    'engine_code' => 'payment.engine_code',
                    'payment_additional_info' => 'payment.payment_additional_info',
                ]
            )
            ->joinLeft(
                ['profile_product' => $this->getTable(ProductSubscriptionProfileInterface::ENTITY_TABLE)],
                'main_table.entity_id = profile_product.subscription_profile_id',
                [
                    'product_id' => 'profile_product.magento_product_id',
                    'product_name' => 'profile_product.name',
                    'product_qty' => 'profile_product.qty',
                    'parent_id' => 'profile_product.parent_id',
                    'product_options' => 'profile_product.custom_options'
                ]
            )
            ->where('parent_id IS NULL');

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
            'main_table.status not in (?)',
            [
                ProfileStatus::STATUS_COMPLETE,
                ProfileStatus::STATUS_CANCELED,
            ]
        )->order(
            SubscriptionProfileOrderInterface::SCHEDULED_AT . ' ASC'
        )->limit(1);

        return $result;
    }
}
