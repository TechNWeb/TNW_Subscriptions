<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterfaceFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterface;
use TNW\Subscriptions\Api\SubscriptionProfileMessageHistoryRepositoryInterface;

class MessageHistoryLogger
{
    /**#@+
     * Messages for subscription profile history.
     */
    const MESSAGE_SUBSCRIPTION_CREATED = 1;
    const MESSAGE_QUOTE_CREATED = 2;
    const MESSAGE_SUBSCRIPTION_STATUS_CHANGED = 3;
    const MESSAGE_SHIPMENT_METHOD_CHANGED = 4;
    const MESSAGE_BILLING_ADDRESS_UPDATED = 5;
    const MESSAGE_PAYMENT_METHOD_CHANGED = 6;
    const MESSAGE_ORDER_CREATED_FROM_QUOTE = 7;
    /**#@-*/

    /**
     * @var SubscriptionProfileMessageHistoryInterfaceFactory
     */
    private $messageHistoryFactory;

    /**
     * @var SubscriptionProfileMessageHistoryRepositoryInterface
     */
    private $messageHistoryRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $date;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $authSession;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * Messages to log.
     *
     * @var array
     */
    private $messages = [
        self::MESSAGE_SUBSCRIPTION_CREATED => 'Subscription Profile %s created.',
        self::MESSAGE_QUOTE_CREATED => 'Quote #%s created. Quote is scheduled to process on %s.',
        self::MESSAGE_ORDER_CREATED_FROM_QUOTE => 'Order #%s created from quote #%s.',
    ];

    /**
     * @param SubscriptionProfileMessageHistoryInterfaceFactory $messageHistoryFactory
     * @param SubscriptionProfileMessageHistoryRepositoryInterface $messageHistoryRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     */
    public function __construct(
        SubscriptionProfileMessageHistoryInterfaceFactory $messageHistoryFactory,
        SubscriptionProfileMessageHistoryRepositoryInterface $messageHistoryRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Sales\Model\OrderRepository $orderRepository
    ) {
        $this->messageHistoryFactory = $messageHistoryFactory;
        $this->messageHistoryRepository = $messageHistoryRepository;
        $this->date = $date;
        $this->authSession = $authSession;
        $this->orderRepository = $orderRepository;
    }

    /***
     * Log subscription profile action to show it in "Change History" tab.
     *
     * @param string $message
     * @param $subscriptionId
     * @param bool $isComment
     * @param bool $isVisibleOnFront
     * @param bool $isAutomatedProcess
     */
    public function log(
        $message,
        $subscriptionId,
        $isComment = false,
        $isVisibleOnFront = false,
        $isAutomatedProcess = false
    ) {
        $createdAt = $this->date->gmtTimestamp();

        /** @var SubscriptionProfileMessageHistoryInterface $messageHistory */
        $messageHistory = $this->messageHistoryFactory->create();
        $messageHistory
            ->setMessage($message)
            ->setParentId($subscriptionId)
            ->setIsComment($isComment)
            ->setIsVisibleOnFront($isVisibleOnFront)
            ->setCreatedAt($createdAt);

        if (!$isAutomatedProcess) {
            $user = $this->authSession->getUser();
            $messageHistory->setUserId($user->getId());
        }

        $this->messageHistoryRepository->save($messageHistory);
    }

    /**
     * Get message by index.
     *
     * @param int $index
     * @return string
     */
    public function getMessage($index)
    {
        $message = '';
        if (isset($this->messages[$index])) {
            $message = __($this->messages[$index]);
        }

        return $message;
    }

    /**
     * Get order increment_id by id.
     *
     * @param $orderId
     *
     * @return null|string
     */
    public function getOrderIncrementIdById($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        return $order->getIncrementId();
    }

    /**
     * Get quote id like increment_id
     *
     * @param $quoteId
     *
     * @return string
     */
    public function getConvertedQuoteId($quoteId)
    {
        return sprintf(\Magento\SalesSequence\Model\Sequence::DEFAULT_PATTERN, null, $quoteId, null);
    }
}
