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
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

/**
 * Reassign subscription to customer.
 */
class ReassignSubscriptions extends Action
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
     * @var Create
     */
    private $createSubscriptionProfile;

    /**
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param DataPersistorInterface $dataPersistor
     * @param Create $createSubscriptionProfile
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        DataPersistorInterface $dataPersistor,
        Create $createSubscriptionProfile
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
        $response->setData('result', true);

        $customerId = $this->dataPersistor->get('existsCustomerId');

        if ($customerId) {
            $this->createSubscriptionProfile->changeCustomerIdInSession($customerId);
            $this->createSubscriptionProfile->reassignQuote($customerId);
        } else {
            $response->setData('result', false);
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
