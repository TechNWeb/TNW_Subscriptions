<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;

/**
 * Resource model for SubscriptionProfileOrder
 */
class SubscriptionProfileOrder extends AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            SubscriptionProfileOrderInterface::MAIN_TABLE,
            SubscriptionProfileOrderInterface::ID
        );
    }

    public function getLastProfileOrderByProfileId($profileId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable(), ['*'])
            ->order($this->getIdFieldName() . ' DESC')
            ->where('subscription_profile_id = ?', $profileId)
            ->limit(1);
        return $connection->fetchRow($select);
    }

    /**
     * Group and return collection of Profile Ids by Magento Order Id
     *
     * @param int $magentoOrderId
     * @return string|null
     * @throws LocalizedException
     */
    public function getProfileIdsByMagentoOrderId(int $magentoOrderId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                $this->getMainTable(),
                [
                    '*',
                    sprintf(
                        'GROUP_CONCAT(%s) AS profile_ids',
                        SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID
                    ),
                ]
            )
            ->where('magento_order_id = ?', $magentoOrderId)
            ->group('magento_order_id')
            ->order(SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID);

        $result = $connection->fetchRow($select);
        return (string)$result['profile_ids'] ?? null;
    }

    /**
     * Populate Sales Order Grid with Profile Ids
     *
     * @param int $magentoOrderId
     * @param string $profileIds
     */
    public function populateSalesOrderGridWithProfileIds(int $magentoOrderId, string $profileIds)
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->getTable('sales_order_grid'),
            ['subscription_profile_id' => $profileIds],
            ['entity_id = ?' => $magentoOrderId]
        );
    }

    /**
     * Populate Sales Order Grid with Profile Ids
     *
     * @param int $magentoOrderId
     * @param string $profileIds
     */
    public function populateRecurringInstallmentData(
        $magentoOrderId,
        $paidRecurring,
        $finalRecurring,
        $firstRecurring,
        $expireCc,
        $totalBilling
    ) {
        $connection = $this->getConnection();
        $connection->update(
            $this->getTable('sales_order_grid'),
            [
                'subscription_paid_installment' => $paidRecurring,
                'subscription_final_installment_date' => $finalRecurring,
                'subscription_first_installment_date' => $firstRecurring,
                'subscription_expire_cc' => $expireCc,
                'subscription_total_static_billing_cycles' => $totalBilling,
            ],
            ['entity_id = ?' => $magentoOrderId]
        );
    }

    /**
     * @param $profileId
     * @return array
     * @throws LocalizedException
     */
    public function getProfileOrdersByProfileId($profileId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable(), ['*'])
            ->order($this->getIdFieldName() . ' DESC')
            ->where('subscription_profile_id = ?', $profileId);
        return $connection->fetchAll($select);
    }
}
