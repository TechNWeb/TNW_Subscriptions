<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Queue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use TNW\Subscriptions\Api\SubscriptionProfileQueueRepositoryInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class Delete
 */
class Delete extends Action
{
    /**
     * Repository for retrieving queue items.
     *
     * @var SubscriptionProfileQueueRepositoryInterface
     */
    private $queueRepository;

    /**
     * Delete constructor.
     * @param Context $context
     * @param SubscriptionProfileQueueRepositoryInterface $queueRepository
     */
    public function __construct(
        Context $context,
        SubscriptionProfileQueueRepositoryInterface $queueRepository
    ) {
        $this->queueRepository = $queueRepository;
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
                $this->queueRepository->deleteById($queueId);
                $this->messageManager->addSuccessMessage(
                    'Record was successfully deleted.',
                    'backend'
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage(), 'backend');
            }
        }

        return $this->resultRedirectFactory
            ->create()
            ->setPath($this->_redirect->getRefererUrl());
    }
}
