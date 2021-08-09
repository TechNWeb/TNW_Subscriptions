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
        $expireCc
    ) {
        $connection = $this->getConnection();
        $connection->update(
            $this->getTable('sales_order_grid'),
            [
                'subscription_paid_installment' => $paidRecurring,
                'subscription_final_installment_date' => $finalRecurring,
                'subscription_first_installment_date' => $firstRecurring,
                'subscription_expire_cc' => $expireCc,
            ],
            ['entity_id = ?' => $magentoOrderId]
        );
    }

    /**
     * @param $subscriptionProfile
     * @return string
     */
    public function getCcEcpiration($subscriptionProfile)
    {
        $getExpireDate = json_decode($subscriptionProfile->getPayment()->getPaymentAdditionalInfo(), true);
        $result = "--";
        if (isset($getExpireDate['cc_exp_month']) && isset($getExpireDate['cc_exp_year'])) {
            $ccExpMonth = (int)$getExpireDate['cc_exp_month'];
            $ccExpYear = (int)$getExpireDate['cc_exp_year'];
            $date = date("m.d.y");
            $currentDate = explode('.', $date);
            switch ($currentDate) {
                case $currentDate['1'] > $ccExpMonth && $currentDate['2'] > $ccExpYear:
                    $result = 'Yes';
                    break;
                case $currentDate['1'] == $ccExpMonth && $currentDate['2'] == $ccExpYear:
                case $currentDate['1'] < $ccExpMonth && $currentDate['2'] < $ccExpYear:
                $result = 'No';
                    break;
            }
        }
        return $result;
    }

    public function getFinalDate($billingFrequency, $totalBillingCycles, $startDate, $term)
    {
        $result = null;
        if ($term == '1') {
            return "--";
        }
        switch ($billingFrequency->getUnit()) {
            case BillingFrequencyUnitType::DAYS:
                $billingCycles = "+" . $totalBillingCycles . " days";
                $result = date("Y-m-d", strtotime($billingCycles, strtotime($startDate)));
                break;
            case BillingFrequencyUnitType::MONTHS:
                $billingCycles = "+" . $totalBillingCycles . " months";
                $result = date("Y-m-d", strtotime($billingCycles, strtotime($startDate)));
                break;
            case BillingFrequencyUnitType::YEARS:
                $billingCycles = "+" . $totalBillingCycles . " years";
                $result = date("Y-m-d", strtotime($billingCycles, strtotime($startDate)));
                break;
        }
        return $result;
    }
}
