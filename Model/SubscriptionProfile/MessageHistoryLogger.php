<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterfaceFactory;
use TNW\Subscriptions\Api\SubscriptionProfileMessageHistoryRepositoryInterface;

/**
 * Class to log Subscription Profile message history.
 */
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
    const MESSAGE_SUBSCRIPTION_PRODUCT_CHANGED = 8;
    const MESSAGE_SUBSCRIPTION_PRODUCT_STOCK = 9;
    const PAYMENT_METHOD_DATA_CHANGED = 10;
    /**#@-*/

    /**#@+
     * Constants for process type.
     */
    const PROCESS_TYPE_FROM_ADMIN = 0;
    const PROCESS_TYPE_AUTOMATED = 1;
    /**#@-*/

    /**
     * Message history factory.
     *
     * @var SubscriptionProfileMessageHistoryInterfaceFactory
     */
    private $messageHistoryFactory;

    /**
     * Message history repository.
     *
     * @var SubscriptionProfileMessageHistoryRepositoryInterface
     */
    private $messageHistoryRepository;

    /**
     * Date.
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $date;

    /**
     * Auth session factory.
     *
     * @var \Magento\Backend\Model\Auth\SessionFactory
     */
    private $authSessionFactory;

    /**
     * Order repository.
     *
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    private $customerSessionFactory;

    /**
     * Messages to log.
     *
     * @var array
     */
    private $messages = [
        self::MESSAGE_SUBSCRIPTION_CREATED => 'Subscription Profile %s created.',
        self::MESSAGE_QUOTE_CREATED => 'Quote #%s created. Quote is scheduled to process on %s (UTC).',
        self::MESSAGE_ORDER_CREATED_FROM_QUOTE => 'Order <a href="{orderUrl|%d}">#%s</a> created from quote #%s.',
        self::MESSAGE_SUBSCRIPTION_STATUS_CHANGED => 'Subscription status changed from <b>%s</b> to <b>%s</b>',
        self::MESSAGE_SUBSCRIPTION_PRODUCT_CHANGED => 'Changed [Product %s] to [Product %s]',
        self::MESSAGE_SUBSCRIPTION_PRODUCT_STOCK => 'Products %s were out of stock and not ordered.',
        self::PAYMENT_METHOD_DATA_CHANGED => 'Payment data changed for %s from %s to %s'
    ];

    /**
     * @param SubscriptionProfileMessageHistoryInterfaceFactory $messageHistoryFactory
     * @param SubscriptionProfileMessageHistoryRepositoryInterface $messageHistoryRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Backend\Model\Auth\SessionFactory $authSessionFactory
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     */
    public function __construct(
        SubscriptionProfileMessageHistoryInterfaceFactory $messageHistoryFactory,
        SubscriptionProfileMessageHistoryRepositoryInterface $messageHistoryRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Backend\Model\Auth\SessionFactory $authSessionFactory,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory
    ) {
        $this->messageHistoryFactory = $messageHistoryFactory;
        $this->messageHistoryRepository = $messageHistoryRepository;
        $this->date = $date;
        $this->authSessionFactory = $authSessionFactory;
        $this->orderRepository = $orderRepository;
        $this->customerSessionFactory = $customerSessionFactory;
    }

    /***
     * Log subscription profile action to show it in "Change History" tab.
     *
     * @param string $message
     * @param int $subscriptionId
     * @param bool $isComment
     * @param bool $isVisibleOnFront
     * @param bool $isAutomatedProcess
     */
    public function log(
        $message,
        $subscriptionId,
        $isComment = false,
        $isVisibleOnFront = true,
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
            $authSession = $this->authSessionFactory->create();
            $user = $authSession->getUser();
            if ($user) {
                $messageHistory->setUserId($user->getId());
            }

            $customerId = $this->customerSessionFactory->create()
                ->getCustomerId();

            if ($customerId) {
                $messageHistory->setCustomerId($customerId);
            }
        }

        $this->messageHistoryRepository->save($messageHistory);
    }

    /**
     * @param $index
     * @param array $params
     * @param $subscriptionId
     * @param bool $isComment
     * @param bool $isVisibleOnFront
     * @param bool $isAutomatedProcess
     */
    public function message(
        $index,
        array $params,
        $subscriptionId,
        $isComment = false,
        $isVisibleOnFront = true,
        $isAutomatedProcess = false
    ) {
        array_unshift($params, $this->getMessage($index));
        $this->log(sprintf(... $params), $subscriptionId, $isComment, $isVisibleOnFront, $isAutomatedProcess);
    }

    /**
     * Get message by index.
     *
     * @param int $index
     * @return string
     * @deprecated
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
     * @param int $orderId
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
     * @param int $quoteId
     *
     * @return string
     */
    public function getConvertedQuoteId($quoteId)
    {
        return sprintf(\Magento\SalesSequence\Model\Sequence::DEFAULT_PATTERN, null, $quoteId, null);
    }
}
