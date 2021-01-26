<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;
use TNW\Subscriptions\Model\Queue as QueueModel;

/**
 * Class Queue - resource queue model
 */
class Queue extends AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * Queue constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->dateTime = $dateTime;
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('tnw_subscriptions_subscription_profile_queue', 'id');
    }

    /**
     * @param $relationIds
     * @param null $makeProcessed
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertItems($relationIds, $makeProcessed = null)
    {
        if (empty($relationIds)) {
            return [];
        }

        if (!\is_array($relationIds)) {
            $relationIds = [$relationIds];
        }

        $fields = [];
        $status = $makeProcessed ? QueueStatus::QUEUE_STATUS_RUNNING : QueueStatus::QUEUE_STATUS_PENDING;
        foreach ($relationIds as $relationId) {
            $fields[] = [
                QueueModel::PROFILE_ORDER_ID => $relationId,
                QueueModel::STATUS => $status,
                QueueModel::MESSAGE => '',
                QueueModel::CREATED_AT => $this->dateTime->gmtDate(),
                QueueModel::UPDATED_AT => $this->dateTime->gmtDate()
            ];
        }

        $connection = $this->getConnection();

        $connection->insertOnDuplicate(
            $this->getMainTable(),
            $fields,
            [QueueModel::MESSAGE, QueueModel::CREATED_AT, QueueModel::UPDATED_AT]
        );

        $select = $connection->select()
            ->from($this->getMainTable(), [QueueModel::ID])
            ->where($connection->prepareSqlCondition(QueueModel::PROFILE_ORDER_ID, ['in'=>$relationIds]));

        return $connection->fetchCol($select);
    }

    /**
     * @param int[]|int $ids
     * @param int $status
     * @param string $message
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateStatus($ids, $status, $message = '')
    {
        if (empty($ids)) {
            return;
        }

        if (!\is_array($ids)) {
            $ids = [$ids];
        }

        $connection = $this->getConnection();
        $connection->update(
            $this->getMainTable(),
            [
                'status' => $status,
                'attempt_count' => new \Zend_Db_Expr('attempt_count + 1'),
                'message' => $message,
                'updated_at' => $this->dateTime->gmtDate(),
            ],
            $connection->prepareSqlCondition(QueueModel::ID, ['in'=>$ids])
        );
    }

    /**
     * @param int[]|int $ids
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteIds($ids)
    {
        if (empty($ids)) {
            return;
        }

        if (!\is_array($ids)) {
            $ids = [$ids];
        }

        $connection = $this->getConnection();
        $connection->delete($this->getMainTable(), $connection->prepareSqlCondition(QueueModel::ID, ['in'=>$ids]));
    }

    /**
     * @param QueueModel $queue
     * @param $relationId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByRelationId(QueueModel $queue, $relationId)
    {
        $connection = $this->getConnection();
        $bind = ['profile_order_id' => $relationId];
        $select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            'profile_order_id = :profile_order_id'
        );

        $queueData = $connection->fetchRow($select, $bind);
        if ($queueData && is_array($queueData) && array_key_exists('id', $queueData)) {
            $queue->setData($queueData);
        }

        return $this;
    }
}
