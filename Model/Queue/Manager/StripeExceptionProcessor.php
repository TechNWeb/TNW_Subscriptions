<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Queue\Manager;

use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Model\EmailNotifierFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\ReBillManager;
use TNW\Subscriptions\Model\Queue as ProcessingQueue;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Payment\Gateway\Http\ClientException;

/**
 * Class StripeExceptionProcessor - stripe engine command exception processor
 */
class StripeExceptionProcessor implements ExceptionProcessorInterface
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
     * @var ReBillManager
     */
    private $reBillManager;

    /**
     * BraintreeExceptionProcessor constructor.
     * @param SubscriptionProfileRepository $profileRepository
     * @param EmailNotifierFactory $emailNotifierFactory
     * @param ReBillManager $reBillManager
     */
    public function __construct(
        SubscriptionProfileRepository $profileRepository,
        EmailNotifierFactory $emailNotifierFactory,
        ReBillManager $reBillManager
    ) {
        $this->reBillManager = $reBillManager;
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
        if ($exception instanceof ClientException && $exception->getCode() == 10001) {
            $reBill = $this->reBillManager->createReBillByFailedGroupQueue($groupQueue);
        }
        foreach ($groupQueue as $queue) {
            $profile = $this->profileRepository->getById($queue->getData('subscription_profile_id'));
            if ($exception instanceof ClientException) {
                if (isset($reBill) && $reBill->getId()) {
                    $this->emailNotifierFactory->create()->paymentVerificationFailed($profile, $reBill);
                }
            }
        }
        if (isset($reBill) && $reBill->getId()) {
            $queueIds = array_map([$this, 'queueIdByQueue'], $groupQueue);
            $queueManager->makeVerification($queueIds, $exception->getMessage(), false);
            return true;
        }

        if ($exception instanceof ClientException) {
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
