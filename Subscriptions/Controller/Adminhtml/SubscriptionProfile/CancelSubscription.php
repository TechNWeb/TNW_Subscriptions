<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\UpdateStatus as UpdateStatusModel;

/**
 * Cancel subscription.
 */
class CancelSubscription extends Action
{
    /**
     * Result json factory.
     *
     * @var JsonFactory
     */
    private $resultJsonFactory;

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
     * @param JsonFactory $jsonFactory
     * @param DataPersistorInterface $dataPersistor
     * @param UpdateStatusModel $updateStatusModel
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        DataPersistorInterface $dataPersistor,
        UpdateStatusModel $updateStatusModel
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->dataPersistor = $dataPersistor;
        $this->updateStatusModel = $updateStatusModel;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $response = new DataObject();
        $response->setData('result', false);

        $value = $this->getRequest()->getParam('value');
        $subscriptionId = $this->dataPersistor->get('subscription_id');

        try {
            if ($value == 'next') {
                $this->updateStatusModel->updateStatusBeforeNextBillingCycle($subscriptionId);
                $response->setData('result', true);
            } elseif ($value == 'now') {
                $this->updateStatusModel->updateStatus($subscriptionId, ProfileStatus::STATUS_CANCELED);
                $response->setData('result', true);
            }
        } catch (\Exception $e) {
            $response->setData('result', false);
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
