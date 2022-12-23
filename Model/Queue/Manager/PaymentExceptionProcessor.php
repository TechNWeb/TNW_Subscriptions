<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Queue\Manager;

use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Model\EmailNotifierFactory;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Model\Queue as ProcessingQueue;

/**
 * Class PaymentExceptionProcessor - process queue manager payment profile exception
 */
class PaymentExceptionProcessor implements ExceptionProcessorInterface
{
    /**
     * Repository for retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * @var EmailNotifierFactory
     */
    private $emailNotifierFactory;

    /**
     * PaymentExceptionProcessor constructor.
     * @param SubscriptionProfileRepository $profileRepository
     * @param EmailNotifierFactory $emailNotifierFactory
     */
    public function __construct(
        SubscriptionProfileRepository $profileRepository,
        EmailNotifierFactory $emailNotifierFactory
    ) {
        $this->emailNotifierFactory = $emailNotifierFactory;
        $this->profileRepository = $profileRepository;
    }

    /**
     * @param $exception
     * @param $groupQueue
     * @param $quote
     * @param $queueManager
     * @return bool|mixed
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     */
    public function process($exception, $groupQueue, $quote, $queueManager)
    {
        if ($exception instanceof CommandException) {
            foreach ($groupQueue as $queue) {
                $profile = $this->profileRepository->getById($queue->getData('subscription_profile_id'));
                $this->emailNotifierFactory->create()->paymentFailed($profile);
            }
            $queueIds = array_map([$this, 'queueIdByQueue'], $groupQueue);
            $queueManager->makeError($queueIds, $exception->getMessage(), true);
            return true;
        }
        return false;
    }

    /**
     * @param ProcessingQueue $queue
     *
     * @return int|null
     */
    public function queueIdByQueue(ProcessingQueue $queue)
    {
        return $queue->getId();
    }
}
