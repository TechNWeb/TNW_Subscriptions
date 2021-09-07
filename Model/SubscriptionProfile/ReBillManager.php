<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Api\ReBillRepositoryInterface;
use TNW\Subscriptions\Api\Data\ReBillInterfaceFactory as ModelFactory;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use Magento\Framework\UrlInterface as UrlBuilder;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;

/**
 * Class ReBillManager - used to manage subscription re-bills
 */
class ReBillManager
{
    /**
     * @var ReBillRepositoryInterface
     */
    private $reBillRepository;

    /**
     * @var ModelFactory
     */
    private $modelFactory;

    /**
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * ReBillManager constructor.
     * @param ReBillRepositoryInterface $reBillRepository
     * @param ModelFactory $modelFactory
     * @param SubscriptionProfileRepository $profileRepository
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        ReBillRepositoryInterface $reBillRepository,
        ModelFactory $modelFactory,
        SubscriptionProfileRepository $profileRepository,
        UrlBuilder $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->profileRepository = $profileRepository;
        $this->modelFactory = $modelFactory;
        $this->reBillRepository = $reBillRepository;
    }

    /**
     * @param $groupQueue
     * @return mixed|\TNW\Subscriptions\Api\Data\ReBillInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createReBillByFailedGroupQueue($groupQueue)
    {
        $reBill = $this->modelFactory->create();
        $queueIds = [];
        $profileIds = [];
        $customerId = null;
        foreach ($groupQueue as $queue) {
            if ($queue->getData('status') != QueueStatus::QUEUE_STATUS_VERIFICATION) {
                $queueIds[] = $queue->getId();
                $subscriptionProfileId = $queue->getData('subscription_profile_id');
                $profileIds[] = $subscriptionProfileId;
                $subscriptionProfile = $this->profileRepository->getById($subscriptionProfileId);
                $customerId = $subscriptionProfile->getCustomerId();
            }
        }
        if ($queueIds && $customerId && $profileIds) {
            $reBill->setQueues($queueIds);
            $reBill->setCustomerId($customerId);
            $reBill->setSubscriptionProfiles($profileIds);
            $reBill->setToken($this->generateTokenByRebill($reBill));
            return $this->reBillRepository->save($reBill);
        } else {
            return $reBill;
        }
    }

    /**
     * @param $reBill
     * @return string
     */
    public function generateTokenByRebill($reBill)
    {
        return md5(
            $reBill->getData('queues') . $reBill->getData('subscription_profiles') . $reBill->getCustomerId()
        );
    }

    /**
     * @param $reBill
     * @return string
     */
    public function getReBillLink($reBill)
    {
        return $this->urlBuilder->getUrl(
            'tnw_subscriptions/subscription_queue/process',
            ['_current' => true, 'token' => $reBill->getToken()]
        );
    }
}
