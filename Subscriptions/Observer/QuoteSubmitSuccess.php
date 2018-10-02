<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class QuoteSubmitSuccess implements ObserverInterface
{
    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    private $profileManager;

    /**
     * @var \TNW\Subscriptions\Model\Quote\ItemGroup
     */
    private $quoteItemGroup;

    /**
     * @var \Magento\Sales\Api\OrderCustomerManagementInterface
     */
    private $orderCustomerService;

    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager,
        \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup,
        \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerService
    ) {
        $this->profileManager = $profileManager;
        $this->quoteItemGroup = $quoteItemGroup;
        $this->orderCustomerService = $orderCustomerService;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getData('quote');
        if (!$quote instanceof \Magento\Quote\Model\Quote || !$quote->getData('is_tnw_subscription')) {
            return;
        }

        $order = $observer->getData('order');
        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getEntityId()) {
            return;
        }

        $groups = array_filter($this->quoteItemGroup->groups($quote->getAllVisibleItems()), function($key) {
            return strcasecmp($key, 'no_option') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        if (empty($groups)) {
            return;
        }

        // Create customer
        if ($order->getCustomerIsGuest()) {
            $quote->setCustomer($this->orderCustomerService->create($order->getEntityId()));
        }

        // Create profile
        foreach ($groups as $groupKey => $quoteItems) {
            $this->profileManager->createByOrder($order, $quote, $quoteItems);
        }
    }
}
