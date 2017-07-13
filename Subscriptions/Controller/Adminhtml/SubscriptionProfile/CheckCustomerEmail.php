<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action;
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
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        CustomerRepository $customerRepository
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->customerRepository = $customerRepository;

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
            $response->setData('fistname', $customer->getFirstname());
            $response->setData('lastname', $customer->getLastname());
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
