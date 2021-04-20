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
     * Process constructor.
     * @param Context $context
     * @param Manager $queueManager
     * @param ProfileProcessor $profileProcessor
     */
    public function __construct(
        Context $context,
        Manager $queueManager,
        ProfileProcessor $profileProcessor
    ) {
        $this->queueManager = $queueManager;
        $this->profileProcessor = $profileProcessor;

        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var int $queueId */
        $queueId = (int)$this->getRequest()->getParam('id', 0);

        if ($queueId) {
            try {
                $collection = $this->queueManager->getBaseCollection();
                $collection->addFilterToMap(Queue::ID, 'main_table.' . Queue::ID);
                $collection->addFieldToFilter(Queue::ID, $queueId);
                /** @var Queue $item */
                $item = $collection->getFirstItem();
                if ($item && $item->getId()) {
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
                    } catch (\TNW\Subscriptions\Exception\ProfileProductsUnsaleableException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                        $this->queueManager->makeCompleted($item->getId(), $e->getMessage());
                    } catch (\Magento\Payment\Gateway\Command\CommandException $e) {
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
