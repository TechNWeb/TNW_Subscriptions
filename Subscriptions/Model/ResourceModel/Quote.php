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
    protected function _getLoadSelect($field, $value, $object)
    {
        return parent::_getLoadSelect($field, $value, $object)
            ->where('is_tnw_subscription = ?', $this->isSubscription);
    }

    /**
     * @inheritdoc
     */
    public function loadByIdWithoutStore($quote, $quoteId)
    {
        $connection = $this->getConnection();
        if ($connection) {
            $select = \Magento\Framework\Model\ResourceModel\Db\AbstractDb::_getLoadSelect('entity_id', $quoteId, $quote)
                ->where('is_tnw_subscription = ?', $this->isSubscription);

            $data = $connection->fetchRow($select);

            if ($data) {
                $quote->setData($data);
            }
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
