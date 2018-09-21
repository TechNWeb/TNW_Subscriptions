<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\UpdateStatus as UpdateStatusModel;

/**
 * Cancel subscription.
 */
class CancelSubscription extends Action
{
    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Model for update status.
     *
     * @var UpdateStatusModel
     */
    private $updateStatusModel;

    /**
     * @param Action\Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param UpdateStatusModel $updateStatusModel
     */
    public function __construct(
        Action\Context $context,
        DataPersistorInterface $dataPersistor,
        UpdateStatusModel $updateStatusModel
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->updateStatusModel = $updateStatusModel;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $option = $this->getRequest()->getPost()->get('cancel_button_popup_options');
        $subscriptionId = $this->dataPersistor->get('subscription_id');

        try {
            if ($option == 'next') {
                $this->updateStatusModel->updateStatusBeforeNextBillingCycle($subscriptionId);
            } elseif ($option == 'now') {
                $this->updateStatusModel->updateStatus($subscriptionId, ProfileStatus::STATUS_CANCELED);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $resultRedirect->setPath('tnw_subscriptions/subscriptionprofile/edit', ['entity_id' => $subscriptionId]);

        return $resultRedirect;
    }
}
