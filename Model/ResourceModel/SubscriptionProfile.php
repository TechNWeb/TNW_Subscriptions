<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
     * @var \Magento\Framework\EntityManager\EntityManager
     */
    private $entityManager;

    /**
     * SubscriptionProfile constructor.
     * @param \Magento\Eav\Model\Entity\Context $context
     * @param \Magento\Framework\EntityManager\EntityManager $entityManager
     * @param array $data
     */
    public function __construct(
        \Magento\Eav\Model\Entity\Context $context,
        \Magento\Framework\EntityManager\EntityManager $entityManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->entityManager = $entityManager;
    }

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
     * @return string
     */
    public function getCurrentValue(\Magento\Framework\Model\AbstractModel $object)
    {
        $select = $this->getConnection()->select()
            ->from(
                ['profileItem' => $this->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                ['total' => new \Zend_Db_Expr('SUM(profileItem.qty)*((SUM(invoiceItem.base_row_total_incl_tax)/SUM(invoiceItem.qty))+IFNULL(SUM(orderItemExtension.base_subs_initial_fee), 0))')]
            )
            ->joinInner(
                ['salesRelative' => $this->getTable('tnw_subscriptions_profile_item_sales_item')],
                'profileItem.entity_id = salesRelative.profile_item_id',
                []
            )
            ->joinInner(
                ['invoiceItem' => $this->getTable('sales_invoice_item')],
                'salesRelative.order_item_id = invoiceItem.order_item_id',
                []
            )
            ->joinLeft(
                ['orderItemExtension' => $this->getTable('tnw_subscriptions_order_item_extension_entity')],
                'invoiceItem.order_item_id = orderItemExtension.item_id',
                []
            )
            ->where('profileItem.subscription_profile_id = ?', $object->getId());

        return (float)$this->getConnection()->fetchOne($select);
    }

    /**
     * Get sum of all generated non-paid quotes for subscription profile.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return string
     */
    public function getTotalValue(\Magento\Framework\Model\AbstractModel $object)
    {
        $connection = $this->getConnection();
        $sql = $connection->select()
            ->from($this->getTable('tnw_subscriptions_subscription_profile_order'), ['COUNT(*)'])
            ->where('subscription_profile_id = ?', $object->getId())
            ->where('magento_order_id IS NULL');

        $futureOrderCount = (float)$connection->fetchOne($sql);

        $sql = $this->getConnection()->select()
            ->from(
                ['profileItem' => $this->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                ['total' => new \Zend_Db_Expr('SUM(profileItem.qty)*((SUM(orderItem.base_row_total_incl_tax)/SUM(orderItem.qty_ordered))+IFNULL(SUM(orderItemExtension.base_subs_initial_fee), 0))')]
            )
            ->joinInner(
                ['salesRelative' => $this->getTable('tnw_subscriptions_profile_item_sales_item')],
                'profileItem.entity_id = salesRelative.profile_item_id',
                []
            )
            ->joinInner(
                ['orderItem' => $this->getTable('sales_order_item')],
                'salesRelative.order_item_id = orderItem.item_id',
                []
            )
            ->joinLeft(
                ['orderItemExtension' => $this->getTable('tnw_subscriptions_order_item_extension_entity')],
                'orderItem.item_id = orderItemExtension.item_id',
                []
            )
            ->where('profileItem.subscription_profile_id = ?', $object->getId())
            ->limit(1);

        $profitOne = $connection->fetchOne($sql);
        return $futureOrderCount * $profitOne;
    }

    /**
     * Get subscription order data.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param $quoteSubmit bool
     * @return array
     */
    public function getLastOrderData(\Magento\Framework\Model\AbstractModel $object, $quoteSubmit)
    {
        $result = [];
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

        return $result ? : [];
    }

    /**
     * Get subscription order data.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return array
     */
    public function getFirstOrderData(\Magento\Framework\Model\AbstractModel $object)
    {
        $result = [];
        $id = $object->getId();

        if ($id) {
            $select = $this->getConnection()->select()
                ->from([
                    'main' => $this->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)
                ])
                ->where('main.subscription_profile_id = ?', $id)
                ->where('main.magento_order_id IS NOT NULL')
                ->order('main.scheduled_at ASC')
                ->limit(1);

            $result = $this->getConnection()
                ->query($select)
                ->fetch();
        }

        return $result ? : [];
    }

    /**
     * Reset firstly loaded attributes
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param integer $entityId
     * @param array|null $attributes
     * @return $this
     */
    public function load($object, $entityId, $attributes = [])
    {
        $this->loadAttributesMetadata($attributes);
        $this->entityManager->load($object, $entityId);
        return $this;
    }

    /**
     * Save entity's attributes into the object's resource
     *
     * @param  \Magento\Framework\Model\AbstractModel $object
     * @return $this
     * @throws \Exception
     */
    public function save(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->entityManager->save($object);
        return $this;
    }
}
