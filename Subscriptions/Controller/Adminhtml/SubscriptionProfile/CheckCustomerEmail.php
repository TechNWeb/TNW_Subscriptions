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
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Check if customer with such email exists.
 */
class CheckCustomerEmail extends Action
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
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param CustomerRepository $customerRepository
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        CustomerRepository $customerRepository,
        DataPersistorInterface $dataPersistor
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->customerRepository = $customerRepository;
        $this->dataPersistor = $dataPersistor;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $response = new DataObject();
        $response->setData('exist', true);

        $request = $this->getRequest();
        $email = $request->getParam('email');

        try {
            $customer = $this->customerRepository->get($email);
        } catch (NoSuchEntityException $e) {
            $response->setData('exist', false);
        }

        if (isset($customer) && $customer && $customer->getId()) {
            $response->setData('customerName', $customer->getFirstname() . $customer->getLastname());
            $response->setData('customerEmail', $email);

            $this->dataPersistor->set('existsCustomerName', $customer->getFirstname() . $customer->getLastname());
            $this->dataPersistor->set('existsCustomerEmail', $email);
        } else {
            $this->dataPersistor->clear('existsCustomerName');
            $this->dataPersistor->clear('existsCustomerEmail');
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
