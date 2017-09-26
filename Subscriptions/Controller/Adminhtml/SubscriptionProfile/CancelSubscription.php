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
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Reassign subscription to customer.
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
     * @var CreateProfile
     */
    private $createSubscriptionProfile;

    /**
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param DataPersistorInterface $dataPersistor
     * @param CreateProfile $createSubscriptionProfile
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        DataPersistorInterface $dataPersistor,
        CreateProfile $createSubscriptionProfile
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->dataPersistor = $dataPersistor;
        $this->createSubscriptionProfile = $createSubscriptionProfile;

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
        try {
            if ($value == 'next') {
                $response->setData('result', true);
            } elseif ($value == 'now') {
                $response->setData('result', true);
            }
        } catch (\Exception $e) {
            $response->setData('result', false);
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
