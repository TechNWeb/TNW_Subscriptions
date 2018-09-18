<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel;

class Quote extends \Magento\Quote\Model\ResourceModel\Quote
{
    /**
     * @var bool
     */
    private $isSubscription;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        $connectionName = null,
        $isSubscription = false
    ) {
        $this->isSubscription = $isSubscription;

        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $sequenceManager,
            $connectionName
        );
    }

    /**
     * @inheritdoc
     */
    public function loadByCustomerId($quote, $customerId)
    {
        $connection = $this->getConnection();
        $select = $this->_getLoadSelect('customer_id', $customerId, $quote)
            ->where('is_active = ?', 1)
            ->where('is_tnw_subscription = ?', $this->isSubscription)
            ->order('updated_at ' . \Magento\Framework\DB\Select::SQL_DESC)
            ->limit(1);

        $data = $connection->fetchRow($select);
        if ($data) {
            $quote->setData($data);
        }

        $this->_afterLoad($quote);

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $object->setData('is_tnw_subscription', $this->isSubscription);
        return parent::_afterLoad($object);
    }
}
