<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status;

use TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterfaceFactory;
use TNW\Subscriptions\Api\SubscriptionProfileStatusHistoryRepositoryInterface;

/**
 * Subscription profile status history logger.
 */
class HistoryLogger
{
    /**
     * Status history factory.
     *
     * @var SubscriptionProfileStatusHistoryInterfaceFactory
     */
    private $statusHistoryFactory;

    /**
     * Status history repository.
     *
     * @var SubscriptionProfileStatusHistoryRepositoryInterface
     */
    private $statusHistoryRepository;

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
     * @var \Magento\Customer\Model\SessionFactory
     */
    private $customerSessionFactory;

    /**
     * @param SubscriptionProfileStatusHistoryInterfaceFactory $statusHistoryFactory
     * @param SubscriptionProfileStatusHistoryRepositoryInterface $statusHistoryRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Backend\Model\Auth\SessionFactory $authSessionFactory
     * @param \Magento\Customer\Model\SessionFactory $customerSessionFactory
     */
    public function __construct(
        SubscriptionProfileStatusHistoryInterfaceFactory $statusHistoryFactory,
        SubscriptionProfileStatusHistoryRepositoryInterface $statusHistoryRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Backend\Model\Auth\SessionFactory $authSessionFactory,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory
    ) {
        $this->statusHistoryFactory = $statusHistoryFactory;
        $this->statusHistoryRepository = $statusHistoryRepository;
        $this->date = $date;
        $this->authSessionFactory = $authSessionFactory;
        $this->customerSessionFactory = $customerSessionFactory;
    }

    /**
     * Log subscription profile status changes.
     *
     * @param int|string $subscriptionProfileId
     * @param int|string $statusOld
     * @param int|string $statusNew
     */
    public function log(
        $subscriptionProfileId,
        $statusOld,
        $statusNew
    ) {
        $changedAt = $this->date->gmtTimestamp();
        list($usec, $sec) = explode(" ", microtime());
        list($whole, $decimal) = explode('.', $usec);
        $changedAtMicro = str_pad(substr($decimal, 0, 6), 6, '0', STR_PAD_RIGHT);

        /** @var SubscriptionProfileStatusHistoryInterface $historyItem */
        $historyItem = $this->statusHistoryFactory->create();
        $historyItem
            ->setSubscriptionProfileId($subscriptionProfileId)
            ->setStatusOld($statusOld)
            ->setStatusNew($statusNew)
            ->setChangedAt($changedAt)
            ->setChangedAtMicro($changedAtMicro);

        $authSession = $this->authSessionFactory->create();
        $user = $authSession->getUser();
        if ($user) {
            $historyItem->setUserId($user->getId());
        }

        $customerId = $this->customerSessionFactory->create()
            ->getCustomerId();

        if ($customerId) {
            $historyItem->setCustomerId($customerId);
        }

        $this->statusHistoryRepository->save($historyItem);
    }
}
