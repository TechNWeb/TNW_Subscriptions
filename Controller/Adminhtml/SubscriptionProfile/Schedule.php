<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;

/**
 * Controller for creating relation of subscription profile.
 */
class Schedule extends Action
{
    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @var SubscriptionProfileOrderRepositoryInterface
     */
    private $subscriptionProfileOrderRepository;

    /**
     * @var Manager
     */
    private $queueManager;

    /**
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * Schedule constructor.
     * @param Action\Context $context
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param SubscriptionProfileOrderRepositoryInterface $subscriptionProfileOrderRepository
     * @param Manager $queueManager
     * @param MessageHistoryLogger $messageHistoryLogger
     */
    public function __construct(
        Action\Context $context,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        SubscriptionProfileOrderRepositoryInterface $subscriptionProfileOrderRepository,
        Manager $queueManager,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->queueManager = $queueManager;
        $this->subscriptionProfileOrderRepository = $subscriptionProfileOrderRepository;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $subscriptionProfileId = $this->getRequest()->getParam('profile_id');
        $profile = $this->subscriptionProfileRepository->getById($subscriptionProfileId);
        $profileOrder = $this->subscriptionProfileOrderRepository
            ->getLastSubscriptionOrderByProfileId($subscriptionProfileId);

        $quoteId = 0;
        if ($profileOrder
            && array_key_exists('magento_order_id', $profileOrder)
            && $profileOrder['magento_order_id']
        ) {
            $quoteId = $profileOrder['magento_quote_id'];
        }
        if ($quoteId) {
            $relations = $this->queueManager->createNewRelationByQuoteId($quoteId, $profile);
        } elseif ($profileOrder) {
            $relations = [$profileOrder['id']];
        }
        if (isset($relations)) {
            $this->queueManager->insertItems($relations);
        }
        $this->messageHistoryLogger->log(
            __('Schedule Manually'),
            $subscriptionProfileId,
            true,
            false
        );
        return $resultRedirect->setPath('*/*/edit', ['entity_id' => $subscriptionProfileId]);
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::SubscriptionProfile_save'
        );
    }
}
