<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Cron;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\PoolInterface;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class ProfileProcessor - cron
 */
class ProfileProcessor
{
    /**
     * Subscriptions context.
     *
     * @var Context
     */
    private $context;

    /**
     * Profile process queue manager.
     *
     * @var Manager
     */
    private $queueManager;

    /**
     * Poll of subscription profile status modifiers.
     *
     * @var PoolInterface
     */
    private $statusProcessorsPool;

    /**
     * Profile Repository
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * @var array
     */
    private $tokenHashGroupMap = [];

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Context $context
     * @param Manager $queueManager
     * @param PoolInterface $statusProcessorsPool
     * @param SubscriptionProfileRepository $profileRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        Manager $queueManager,
        PoolInterface $statusProcessorsPool,
        SubscriptionProfileRepository $profileRepository,
        SerializerInterface $serializer
    ) {
        $this->context = $context;
        $this->queueManager = $queueManager;
        $this->statusProcessorsPool = $statusProcessorsPool;
        $this->profileRepository = $profileRepository;
        $this->serializer = $serializer;
    }

    /**
     * Processes profile queue.
     *
     * @param int $websiteId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($websiteId)
    {
        $canceledCollection = $this->queueManager->getBaseCollection()
            ->addFieldToFilter('profile.status', ProfileStatus::STATUS_CANCELED)
            ->addFieldToFilter('main_table.status', QueueStatus::QUEUE_STATUS_PENDING);

        $this->queueManager->makeDelete($canceledCollection->getAllIds());

        // Queue collection
        $collectionToday = $this->queueManager->getCollectionToday($websiteId);

        // Getting Profile IDs
        $profileIds = array_map([$this, 'profileIdByQueue'], $collectionToday->getItems());

        $this->profileRepository->setAutomatedProcessFlag(true);

        // Filtering not active
        $activeQueueList = array_filter($collectionToday->getItems(), [$this, 'filterQueue']);

        foreach ($this->groupedQueue($activeQueueList) as $queues) {
            $queueIds = array_map([$this, 'queueIdByQueue'], $queues);
            $this->queueManager->makeRunning($queueIds);

            try {
                $this->queueManager->placeOrderByGroupQueue($queues);
                $this->queueManager->makeCompleted($queueIds);
                $orderProcessHasError = false;
            } catch (\TNW\Subscriptions\Exception\ProfileProductsUnsaleableException $e) {
                $this->context->messageError($e->getMessage());
                $this->queueManager->makeCompleted($queueIds, $e->getMessage());
                $orderProcessHasError = false;
            } catch (\Magento\Payment\Gateway\Command\CommandException $e) {
                //TODO: add payment error processor for each payment engine
                $this->context->messageError($e->getMessage());
                if ($e->getCode() == 2099) {
                    $this->queueManager->makeVerification($queueIds, $e->getMessage(), false);
                } else {
                    $this->queueManager->makeError($queueIds, $e->getMessage(), true);
                }
                $orderProcessHasError = false;
            } catch (\Exception $e) {
                $this->context->messageError('Error on processing profile: %s', $e);
                $this->queueManager->makeError($queueIds, $e->getMessage());
                $orderProcessHasError = false;
            } finally {
                if (!isset($orderProcessHasError)) {
                    $this->context->messageError('Error on processing profile. (See cron log).');
                    $this->queueManager->makeError($queueIds, __('Error on processing profile. (See cron log).'));
                    $this->updateProfilesStatuses($profileIds);
                }
            }
        }

        $this->updateProfilesStatuses($profileIds);
    }

    /**
     * @param $queueIds
     * @param $customPaymentData
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function processByQueueIds($queueIds, $customPaymentData)
    {
        $collectionToday = $this->queueManager->getCollectionByQueueIds($queueIds);
        $profileIds = array_map([$this, 'profileIdByQueue'], $collectionToday->getItems());
        $this->profileRepository->setAutomatedProcessFlag(true);
        $activeQueueList = array_filter($collectionToday->getItems(), [$this, 'filterQueue']);
        $this->queueManager->setCustomerGroupQueuePaymentData($customPaymentData);
        foreach ($this->groupedQueue($activeQueueList) as $queues) {
            $queueIds = array_map([$this, 'queueIdByQueue'], $queues);
            $this->queueManager->makeRunning($queueIds);
            try {
                $this->queueManager->placeOrderByGroupQueue($queues);
                $this->queueManager->makeCompleted($queueIds);
            } catch (\TNW\Subscriptions\Exception\ProfileProductsUnsaleableException $e) {
                $this->context->messageError($e->getMessage());
                $this->queueManager->makeVerification($queueIds, $e->getMessage());
            } catch (\Magento\Payment\Gateway\Command\CommandException $e) {
                $this->context->messageError($e->getMessage());
                $this->queueManager->makeVerification($queueIds, $e->getMessage());
                throw $e;
            }
        }
        $this->updateProfilesStatuses($profileIds);
    }

    /**
     * @param \TNW\Subscriptions\Model\Queue $queue
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function filterQueue(\TNW\Subscriptions\Model\Queue $queue)
    {
        $queueId = $queue->getId();

        try {
            $profile = $this->profileByQueue($queue);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->queueManager->makeError($queueId, $e->getMessage());
            return false;
        }

        switch ($profile->getStatus()) {
            case ProfileStatus::STATUS_CANCELED:
                return false;

            case ProfileStatus::STATUS_SUSPENDED:
                $this->queueManager->makeSkipped($queueId, __('Profile is Suspended, skipping...'));
                return false;

            case ProfileStatus::STATUS_COMPLETE:
                $this->queueManager->makeSkipped($queueId, __('Profile is Complete, skipping...'));
                return false;

            case ProfileStatus::STATUS_HOLDED:
                $result = true;
                if (!$profile->getSkipBillingCycles()) {
                    $this->queueManager->makeSkipped($queueId, __('Profile is On Hold, skipping...'));
                    $result = false;
                } elseif ($this->passWithoutProcessing($queue)) {
                    $result = false;
                }
                return $result;

            default:
                if ($this->passWithoutProcessing($queue)) {
                    return false;
                }
                return true;
        }
    }

    /**
     * @param \TNW\Subscriptions\Model\Queue $queue
     *
     * @return int|null
     */
    public function queueIdByQueue(\TNW\Subscriptions\Model\Queue $queue)
    {
        return $queue->getId();
    }

    /**
     * @param \TNW\Subscriptions\Model\Queue $queue
     *
     * @return int
     */
    public function profileIdByQueue(\TNW\Subscriptions\Model\Queue $queue)
    {
        return (int)$queue->getData('subscription_profile_id');
    }

    /**
     * @param \TNW\Subscriptions\Model\Queue $queue
     *
     * @return SubscriptionProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function profileByQueue(\TNW\Subscriptions\Model\Queue $queue)
    {
        return $this->profileRepository->getById($this->profileIdByQueue($queue));
    }

    /**
     * @param \TNW\Subscriptions\Model\Queue[] $queues
     *
     * @return \TNW\Subscriptions\Model\Queue[][]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function groupedQueue($queues)
    {
        $groupProfile = [];
        foreach ($queues as $queue) {
            $profile = $this->profileByQueue($queue);
            $groupProfile[$this->groupKey($profile)][] = $queue;
        }

        return $groupProfile;
    }

    /**
     * @param SubscriptionProfileInterface $profile
     *
     * @return string
     */
    private function groupKey(SubscriptionProfileInterface $profile)
    {
        $part[] = $profile->getCustomerId();
        $part[] = $profile->getPayment()->getEngineCode();

        $gatewayToken = $profile->getPayment()->getPaymentToken();
        $hashTempId = array_search($gatewayToken, $this->tokenHashGroupMap);
        if ($hashTempId !== false) {
            $part[] = $hashTempId;
        } else {
            $this->tokenHashGroupMap[] = $gatewayToken;
            $part[] = count($this->tokenHashGroupMap) - 1;
        }

        if (!$profile->getIsVirtual()) {
            /** @var \TNW\Subscriptions\Model\SubscriptionProfile\Address $address */
            $address = $profile->getShippingAddress();

            $part[] = $profile->getShippingMethod();
            $part[] = $this->serializer->serialize([
                $address->getFirstname(),
                $address->getMiddlename(),
                $address->getLastname(),
                $address->getPostcode(),
                $address->getRegion(),
                $address->getRegionId(),
                $address->getCity(),
                $address->getStreet(),
                $address->getTelephone(),
                $address->getCompany(),
            ]);
        }

        return implode('/', $part);
    }

    /**
     * Updates profile statuses after queue processing.
     *
     * @param array $allIds
     */
    public function updateProfilesStatuses(array $allIds)
    {
        if (empty($allIds)) {
            return;
        }

        try {
            foreach ($this->statusProcessorsPool->getProcessorsInstances() as $modifier) {
                $modifier->process($allIds);
            }
        } catch (\Exception $e) {
            $this->context->messageError('Error on updating profile statuses. %s', $e->getMessage());
        }
    }

    /**
     * Check if queue item need to be passed without processing.
     * This items later may be used to change their statuses.
     *
     * @param \TNW\Subscriptions\Model\Queue $item
     * @return bool
     */
    private function passWithoutProcessing(\TNW\Subscriptions\Model\Queue $item)
    {
        $result =
            $item->getData(SubscriptionProfile::CANCEL_BEFORE_NEXT_CYCLE);

        return $result;
    }
}
