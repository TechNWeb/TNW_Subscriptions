<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Info;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;

/**
 * Subscription order history block at customer account dashboard.
 */
class OrderHistory extends ContentAbstract
{
    /**
     * @var string
     */
    protected $_template = 'subscription/order-history.phtml';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private $orders;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \TNW\Subscriptions\Model\MessagePool $messagePool
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \TNW\Subscriptions\Model\MessagePool $messagePool,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $messagePool, $data);
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrderHistory()
    {
        if (!($profileId = $this->_request->getParam('entity_id'))) {
            return false;
        }

        if (!$this->orders) {
            $this->orders = $this->orderCollectionFactory->create();
            $this->orders->join(
                ['relation' => $this->orders->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
                'main_table.entity_id=relation.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID,
                []
            )
                ->addFieldToFilter('relation.subscription_profile_id', $profileId);
        }

        return $this->orders;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getOrderHistory()) {
            $pager = $this->getLayout()
                ->createBlock(\Magento\Theme\Block\Html\Pager::class, 'subscription.info.order.history.pager')
                ->setCollection($this->getOrderHistory());

            $this->setChild('pager', $pager);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @param object $order
     * @return string
     */
    public function getViewUrl($order)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $order->getId()]);
    }
}
