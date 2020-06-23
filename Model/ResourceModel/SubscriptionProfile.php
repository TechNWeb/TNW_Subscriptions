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
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var $invoiceItems
     */
    private $invoiceItems;

    /**
     * SubscriptionProfile constructor.
     * @param \Magento\Eav\Model\Entity\Context $context
     * @param \Magento\Framework\EntityManager\EntityManager $entityManager
     * @param array $data
     */
    public function __construct(
        \Magento\Eav\Model\Entity\Context $context,
        \Magento\Framework\EntityManager\EntityManager $entityManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->entityManager = $entityManager;
        $this->timezone = $timezone;
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
        $invoiceItems = $this->getInvoiceItems($object);
        $profit = 0;
        foreach ($invoiceItems as $item) {
            $profit += $item['base_row_total_incl_tax'];
        }

        return (float) $profit;
    }

    /**
     * Get sum of all generated non-paid quotes for subscription profile.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return string
     */
    public function getTotalValue(\Magento\Framework\Model\AbstractModel $object)
    {
        $invoiceItems = $this->getInvoiceItems($object);
        $lastInvoiceItem = array_pop($invoiceItems);
        $profitOfLastItem = $lastInvoiceItem['base_row_total_incl_tax'];
        if ($object->getTerm() == 1) {
            if ($object->getUnit() == 3) {
                $profit = $profitOfLastItem * 365;
            } else {
                $profit = $profitOfLastItem * 12;
            }
        } else {
            $profit = $profitOfLastItem * $object->getTotalBillingCycles();
        }
        return $profit;
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

    /**
     * Get last invoice items
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return array
     */
    private function getInvoiceItems(\Magento\Framework\Model\AbstractModel $object)
    {
        if (!$this->invoiceItems) {
            $select = $this->getConnection()->select()
                ->from(
                    ['invoiceItem' => $this->getTable('sales_invoice_item')]
                )
                ->joinInner(
                    ['salesRelative' => $this->getTable('tnw_subscriptions_profile_item_sales_item')],
                    'invoiceItem.order_item_id = salesRelative.order_item_id',
                    []
                )
                ->joinInner(
                    ['profileItem' => $this->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                    'salesRelative.profile_item_id = profileItem.entity_id',
                    []
                )
                ->where('profileItem.subscription_profile_id = ?', $object->getId());
            $this->invoiceItems = $this->getConnection()->fetchAll($select);
        }
        return $this->invoiceItems;
    }
}
