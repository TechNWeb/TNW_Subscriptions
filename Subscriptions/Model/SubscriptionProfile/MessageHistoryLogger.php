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
     * Messages to log.
     *
     * @var array
     */
    private $messages = [
        self::MESSAGE_SUBSCRIPTION_CREATED => 'Subscription Profile %s created.'
    ];

    /**
     * @param SubscriptionProfileMessageHistoryInterfaceFactory $messageHistoryFactory
     * @param SubscriptionProfileMessageHistoryRepositoryInterface $messageHistoryRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        SubscriptionProfileMessageHistoryInterfaceFactory $messageHistoryFactory,
        SubscriptionProfileMessageHistoryRepositoryInterface $messageHistoryRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        $this->messageHistoryFactory = $messageHistoryFactory;
        $this->messageHistoryRepository = $messageHistoryRepository;
        $this->date = $date;
        $this->authSession = $authSession;
    }

    /***
     * Log subscription profile action to show it in "Change History" tab.
     *
     * @param string $message
     * @param int $subscriptionId
     * @param bool $isUserMessage
     * @param bool $isVisibleOnFront
     */
    public function log($message, $subscriptionId, $isUserMessage = false, $isVisibleOnFront = false)
    {
        $createdAt = $this->date->gmtTimestamp();

        $user = $this->authSession->getUser();

        /** @var SubscriptionProfileMessageHistoryInterface $messageHistory */
        $messageHistory = $this->messageHistoryFactory->create();
        $messageHistory
            ->setMessage($message)
            ->setUserId($user->getId())
            ->setParentId($subscriptionId)
            ->setIsUserMessage($isUserMessage)
            ->setIsVisibleOnFront($isVisibleOnFront)
            ->setCreatedAt($createdAt);

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
}
