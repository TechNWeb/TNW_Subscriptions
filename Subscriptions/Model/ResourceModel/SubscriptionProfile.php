<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;

/**
 * Resource model for Subscription Profile.
 */
class SubscriptionProfile extends AbstractEntity
{
    /**
     * {@inheritdoc}
     */
    public function getEntityType()
    {
        if (empty($this->_type)) {
            $this->setType(\TNW\Subscriptions\Model\SubscriptionProfile::ENTITY);
        }

        return parent::getEntityType();
    }

    /**
     * Get sum of all paid subscription profile orders.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @throws \Zend_Db_Statement_Exception
     * @return string
     */
    public function getCurrentValue(\Magento\Framework\Model\AbstractModel $object)
    {
        $id = $object->getId();
        $select = $this->getConnection()->select()
            ->from(
                ['profile_order' => $this->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
                ['total' => new \Zend_Db_Expr('sum(sales_order.' . OrderInterface::GRAND_TOTAL . ')')]
            )->join(
            $this->getTable('sales_order'),
            'sales_order.entity_id = profile_order.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID,
            []
        )->where('profile_order.' . SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID . ' = ?', $id)
            ->where('sales_order.status <> ?', \Magento\Sales\Model\Order::STATE_CANCELED);

        return $this->getConnection()->query($select)->fetchColumn();
    }

    /**
     * Get sum of all generated non-paid quotes for subscription profile.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @throws \Zend_Db_Statement_Exception
     * @return string
     */
    public function getTotalValue(\Magento\Framework\Model\AbstractModel $object)
    {
        $id = $object->getId();
        $select = $this->getConnection()->select()
            ->from(
                ['profile_order' => $this->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
                ['total' => new \Zend_Db_Expr('sum(quote.' . OrderInterface::GRAND_TOTAL . ')')]
            )->join(
                $this->getTable('quote'),
                'quote.entity_id = profile_order.' . SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID,
                []
            )->where('profile_order.' . SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID . ' = ?', $id)
            ->where(
                new \Zend_Db_Expr('profile_order.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID . ' is NULL')
            );

        return $this->getConnection()->query($select)->fetchColumn();
    }

    /**
     * Get subscription order data.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param $quoteSubmit bool
     * @return array|false
     */
    public function getLastOrderData(\Magento\Framework\Model\AbstractModel $object, $quoteSubmit)
    {
        $result = false;
        $id = $object->getId();

        if ($id) {
            $quoteSubmitExpression = $quoteSubmit ? 'main.magento_order_id IS NOT NULL'
                : 'main.magento_order_id IS NULL';

            $select = $this->getConnection()->select()
                ->from(
                    [
                        'main' => $this->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)
                    ]
                )->where('main.subscription_profile_id = ?', $id)
                ->where($quoteSubmitExpression)
                ->order('main.scheduled_at DESC')
                ->limit(1);

            $result = $this->getConnection()->query($select)->fetch();
        }

        return $result;
    }
}
