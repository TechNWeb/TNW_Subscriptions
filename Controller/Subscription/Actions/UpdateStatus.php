<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\Actions;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\Context;
use TNW\Subscriptions\Block\Subscription\History;
use TNW\Subscriptions\Block\Subscription\Summary\Overview;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\StatusManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Controller\Subscription\Items;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\UpdateStatus as UpdateStatusModel;

/**
 * Controller for subscription history at customer account dashboard.
 */
class UpdateStatus extends \Magento\Framework\App\Action\Action
{
    /**
     * Model for update status.
     *
     * @var UpdateStatusModel
     */
    private $updateStatusModel;

    /**
     * Repository profile
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * The Manager that define logic of status change on Subscription Profile
     *
     * @var StatusManager
     */
    private $statusManager;

    /**
     * Profile status data source
     *
     * @var ProfileStatus
     */
    private $statusSource;

    /**
     * Message history logger
     *
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * Subscription items at customer account
     *
     * @var Items
     */
    private $subscriptionItems;

    /**
     * UpdateStatus constructor.
     * @param Context $context
     * @param SubscriptionProfileRepository $profileRepository
     * @param StatusManager $statusManager
     * @param ProfileStatus $statusSource
     * @param MessageHistoryLogger $messageHistoryLogger
     * @param Items $subscriptionItems
     * @param UpdateStatusModel $updateStatusModel
     */
    public function __construct(
        Context $context,
        SubscriptionProfileRepository $profileRepository,
        StatusManager $statusManager,
        ProfileStatus $statusSource,
        MessageHistoryLogger $messageHistoryLogger,
        Items $subscriptionItems,
        UpdateStatusModel $updateStatusModel
    ) {
        $this->updateStatusModel = $updateStatusModel;
        $this->profileRepository = $profileRepository;
        $this->statusManager = $statusManager;
        $this->statusSource = $statusSource;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->subscriptionItems = $subscriptionItems;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getParam('entity_id');
        $newStatus = $this->getRequest()->getParam('status');

        try {
            if (!$this->subscriptionItems->canViewSubscriptionById($profileId)) {
                throw new \Magento\Framework\Exception\NoSuchEntityException();
            }
            $model = $this->updateStatusModel->updateStatus(
                $profileId,
                $newStatus,
                $this->getRequest()->getParam('suspension_type') == 'billing_cycles'
                    ? $this->getRequest()->getParam('cycles_count')
                    : 0
            );
            if ($model != null && $this->_request->isAjax()) {
                $this->messageManager->addComplexSuccessMessage(
                    'addHtmlMessage',
                    [
                        'subs_label' => $model->getLabel(),
                        'subs_url' => $this->_url->getUrl(
                            'tnw_subscriptions/subscription/edit',
                            [
                                'entity_id' => $profileId
                            ]
                        ),
                        'subs_status' => $this->statusSource->getLabelByValue($newStatus)->getText()
                    ]
                );
                return $this->getResponse()->representJson('{"error":"false"}');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            if ($this->_request->isAjax()) {
                return $this->getResponse()->representJson('{"error":"true"}');
            }
        }
        return $this->getRedirect();
    }

    /**
     * Retrieve redirect model
     *
     * @return Redirect
     */
    private function getRedirect()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirect = $this->getRequest()->getParam('redirect');
        switch ($redirect) {
            case History::REDIRECT:
                $resultRedirect->setPath('*/subscription/history');
                break;
            case Overview::REDIRECT:
                $resultRedirect->setPath(
                    '*/subscription/edit',
                    ['entity_id' => $this->getRequest()->getParam('entity_id')]
                );
                break;
        }

        return $resultRedirect;
    }
}
