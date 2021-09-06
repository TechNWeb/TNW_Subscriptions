<?php

namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SubscriptionProfileProfit extends AbstractDb
{

    /**
     * Initialization
     */
    protected function _construct()
    {
        $this->_init(
            'tnw_subscriptions_profile_profit',
            'profile_id'
        );
    }

    /**
     * @param $id
     * @param $profitType
     * @return array
     * @throws LocalizedException
     */
    public function getTotalProfitById($id, $profitType)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable(), ['*'])
            ->order($this->getIdFieldName() . ' DESC')
            ->where('profile_id = ?', $id)
            ->where('profit_type =?', $profitType);
        return $connection->fetchAll($select);
    }

    /**
     * @param $data
     * @throws LocalizedException
     */
    public function setTotalProfit($data)
    {
        $connection = $this->getConnection();

        if (isset($data)) {
            $connection->insert($this->getMainTable(), [
                'profile_id' => $data['profile_id'],
                'profit_type' => $data['profit_type'],
                'total_profit' => $data['total_profit']
            ]);
        }
    }

    /**
     * @param $data
     * @throws LocalizedException
     */
    public function updateTotalProfit($data)
    {
        $connection = $this->getConnection();
        if (isset($data)) {
            $connection->update(
                $this->getTable($this->getMainTable()),
                [
                    'total_profit' => $data['total_profit'],
                ],
                [
                    'profile_id = ?' => $data['profile_id'],
                    'profit_type' => $data['profit_type']
                ]
            );
        }
    }
}
