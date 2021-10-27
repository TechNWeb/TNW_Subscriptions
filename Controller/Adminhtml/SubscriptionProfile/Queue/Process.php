<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Queue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Cron\ProfileProcessor;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Exception\ProfileProductsUnsaleableException;
use Magento\Payment\Gateway\Command\CommandException;
use TNW\Subscriptions\Api\SubscriptionProfileQueueRepositoryInterface;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface;

/**
 * Class Process- controller
 */
class Process extends Action
{
    /**
     * Profile process queue manager.
     *
     * @var Manager
     */
    private $queueManager;

    /**
     * Profile processor.
     *
     * @var ProfileProcessor
     */
    private $profileProcessor;

    /**
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * @var SubscriptionProfileQueueRepositoryInterface
     */
    private $queueRepository;

    /**
     * @var SubscriptionProfileOrderRepositoryInterface
     */
    private $subscriptionProfileOrderRepository;

    /**
     * Process constructor.
     * @param Context $context
     * @param Manager $queueManager
     * @param ProfileProcessor $profileProcessor
     * @param SubscriptionProfileRepository $profileRepository
     * @param ProfileStatus $profileStatus
     * @param SubscriptionProfileQueueRepositoryInterface $queueRepository
     * @param SubscriptionProfileOrderRepositoryInterface $subscriptionProfileOrderRepository
     */
    public function __construct(
        Context $context,
        Manager $queueManager,
        ProfileProcessor $profileProcessor,
        SubscriptionProfileRepository $profileRepository,
        ProfileStatus $profileStatus,
        SubscriptionProfileQueueRepositoryInterface $queueRepository,
        SubscriptionProfileOrderRepositoryInterface $subscriptionProfileOrderRepository
    ) {
        $this->subscriptionProfileOrderRepository = $subscriptionProfileOrderRepository;
        $this->queueRepository = $queueRepository;
        $this->queueManager = $queueManager;
        $this->profileProcessor = $profileProcessor;
        $this->profileRepository = $profileRepository;
        $this->profileStatus = $profileStatus;

        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var int $queueId */
        $queueId = (int) $this->getRequest()->getParam('id', 0);
        $acceptableStatuses = [
            ProfileStatus::STATUS_ACTIVE,
            ProfileStatus::STATUS_PAST_DUE,
            ProfileStatus::STATUS_TRIAL
        ];
        if ($queueId) {
            try {
                $collection = $this->queueManager->getBaseCollection();
                $collection->addFilterToMap(Queue::ID, 'main_table.' . Queue::ID);
                $collection->addFieldToFilter(Queue::ID, $queueId);
                /** @var Queue $item */
                $item = $collection->getFirstItem();
                $profile = $this->profileRepository->getById($item->getSubscriptionProfileId());
                $queueModel = $this->queueRepository->getById($queueId);
                if ($item && $item->getId() && in_array($profile->getStatus(), $acceptableStatuses)) {
                    if ($queueModel->getStatus() == QueueStatus::QUEUE_STATUS_RUNNING) {
                        $profileOrderId = $queueModel->getProfileOrderId();
                        $profileOrder = $this->subscriptionProfileOrderRepository->getById($profileOrderId);
                        if (date('Ymd')
                            == date('Ymd', strtotime($profileOrder->getScheduledAt()))
                        ) {
                            throw new \Exception('Can`t manually process running profile.');
                        }
                    }
                    if ($queueModel->getStatus() == QueueStatus::QUEUE_STATUS_COMPLETE) {
                        throw new \Exception('Can`t process completed profile.');
                    }
                    $this->queueManager->makeRunning($queueId);
                    try {
                        $this->queueManager->placeOrderByGroupQueue([$item]);
                        $this->queueManager->makeCompleted($item->getId());
                        $this->profileProcessor->updateProfilesStatuses(
                            [$item->getSubscriptionProfileId()]
                        );
                        $this->messageManager->addSuccessMessage(
                            'Record was successfully processed.',
                            'backend'
                        );
                    } catch (ProfileProductsUnsaleableException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                        $this->queueManager->makeCompleted($item->getId(), $e->getMessage());
                    } catch (CommandException $e) {
                        $this->queueManager->makeError($item->getId(), $e->getMessage(), true);
                        $this->messageManager->addErrorMessage(
                            $e->getMessage(),
                            'backend'
                        );
                    } catch (\Exception $e) {
                        $this->queueManager->makeError($item->getId(), $e->getMessage());
                        $this->messageManager->addErrorMessage(
                            $e->getMessage(),
                            'backend'
                        );
                    }
                } else {
                    $statusLabel = '';
                    foreach ($this->profileStatus->getAllOptions() as $option) {
                        if ($option['value'] == $profile->getStatus()) {
                            $statusLabel = $option['label'];
                        }
                    }
                    $this->queueManager->makeError(
                        $item->getId(),
                        'Profile is ' . $statusLabel . ', skipping...'
                    );
                    $this->messageManager->addErrorMessage('Record can not be processed.', 'backend');
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage(), 'backend');
            }
        }


        return $this->resultRedirectFactory
            ->create()
            ->setPath($this->_redirect->getRefererUrl());
    }
}
