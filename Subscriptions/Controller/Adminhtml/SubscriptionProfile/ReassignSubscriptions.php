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
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use TNW\Subscriptions\Model\Backend\Session\Quote;

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
     * Customer repository.
     *
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Admin session.
     *
     * @var Quote
     */
    private $session;

    /**
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param CustomerRepository $customerRepository
     * @param DataPersistorInterface $dataPersistor
     * @param Quote $session
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        CustomerRepository $customerRepository,
        DataPersistorInterface $dataPersistor,
        Quote $session
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->customerRepository = $customerRepository;
        $this->dataPersistor = $dataPersistor;
        $this->session = $session;

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
            //todo ask Misha if need to do some more ?
            $this->session->setCustomerId($customerId);
        } else {
            $response->setData('result', false);
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
