<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder;

/**
 * Class Total Price column
 */
class Total extends Column
{
    /**
     * @var SubscriptionProfile
     */
    private $subscriptionProfileResource;
    
    /**
     * @var SubscriptionProfileOrder
     */
    private $subscriptionProfileOrderResource;

    /**
     * Flat sales order resource
     *
     * @var OrderResource
     */
    private $orderResource;

    /**
     * Last order totals for profiles
     *
     * @var array
     */
    private $profileGrandTotals;

    /**
     * Convert price value helper
     *
     * @var PriceCurrencyInterface
     */
    private $priceFormatter;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SubscriptionProfile $subscriptionProfileResource
     * @param SubscriptionProfileOrder $subscriptionProfileOrderResource
     * @param OrderResource $orderResource
     * @param PriceCurrencyInterface $priceFormatter
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SubscriptionProfile $subscriptionProfileResource,
        SubscriptionProfileOrder $subscriptionProfileOrderResource,
        OrderResource $orderResource,
        PriceCurrencyInterface $priceFormatter,
        array $components = [],
        array $data = []
    ) {
        $this->subscriptionProfileResource = $subscriptionProfileResource;
        $this->subscriptionProfileOrderResource = $subscriptionProfileOrderResource;
        $this->orderResource = $orderResource;
        $this->priceFormatter = $priceFormatter;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $totals = $this->getProfileGrandTotals($dataSource['data']['items']);
            foreach ($dataSource['data']['items'] as & $item) {
                $profileId = $item[SubscriptionProfileInterface::ID];
                $total = isset($totals[$profileId]) ? $totals[$profileId] : null;
                
                if ($total) {
                    $currencyCode = isset($item[SubscriptionProfileInterface::PROFILE_CURRENCY_CODE]) ?
                        $item[SubscriptionProfileInterface::PROFILE_CURRENCY_CODE] : null;
                    $total = $this->priceFormatter->format(
                        $total,
                        false,
                        null,
                        null,
                        $currencyCode
                    );
                }

                $item[$this->getData('name')] = $total;
            }
        }

        return $dataSource;
    }

    /**
     * Retrieve grand total values for subscription profiles
     *
     * @param array $dataSourceItems
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProfileGrandTotals($dataSourceItems)
    {
        if ($this->profileGrandTotals === null) {
            $this->profileGrandTotals = [];

            $profileIdField = SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID;
            $innerOrderSelect = $this->subscriptionProfileOrderResource->getConnection()->select()
                ->from(
                    ['profile_order_2' => $this->subscriptionProfileOrderResource->getMainTable()],
                    ['max(profile_order_2.' . SubscriptionProfileOrderInterface::SCHEDULED_AT . ')']
                )
                ->where("profile_order_2.{$profileIdField}= profile." . SubscriptionProfileInterface::ID)
                ->where('profile_order_2.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID . ' IS NOT NULL');

            $profileIds = array_column($dataSourceItems, SubscriptionProfileInterface::ID);
            $select = $this->subscriptionProfileOrderResource->getConnection()->select()
                ->from(
                    ['profile' => $this->subscriptionProfileResource->getEntityTable()],
                    [SubscriptionProfileInterface::ID]
                )
                ->join(
                    ['profile_order' => $this->subscriptionProfileOrderResource->getMainTable()],
                    'profile.' . SubscriptionProfileInterface::ID . ' = profile_order.' . $profileIdField
                        . ' AND profile_order.' . SubscriptionProfileOrderInterface::SCHEDULED_AT
                        . ' IN (' . $innerOrderSelect . ')',
                    []
                )
                ->join(
                    ['mage_order' => $this->orderResource->getMainTable()],
                    "profile_order.{$profileIdField} = mage_order." . OrderInterface::ENTITY_ID,
                    [OrderInterface::GRAND_TOTAL]
                )
                ->where('profile.' . SubscriptionProfileInterface::ID . ' IN (?)', $profileIds);

            foreach ($this->subscriptionProfileOrderResource->getConnection()->fetchAll($select) as $row) {
                $this->profileGrandTotals[$row[SubscriptionProfileInterface::ID]] = $row[OrderInterface::GRAND_TOTAL];
            }
        }

        return $this->profileGrandTotals;
    }
}
