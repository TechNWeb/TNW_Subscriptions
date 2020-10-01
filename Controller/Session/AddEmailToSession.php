<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Session;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Add customer email to session.
 */
class AddEmailToSession extends Action
{
    /**
     * Customer session.
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * Json factory.
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
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param CustomerRepository $customerRepository
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        CustomerRepository $customerRepository,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);

        $this->customerRepository = $customerRepository;
        $this->session = $session;
        $this->resultJsonFactory = $jsonFactory;
    }

    /**
     * Add subscription email to session
     *
     * @inheritdoc
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $response->setData('error', false);
        try {
            $email = $this->_request->getParam('customer_email');
            $customer = $this->tryToGetCustomer($email);
            if ($customer && $customer->getId()) {
                $response->addData([
                    'error_message' => __('There is already an account with this email address.'),
                    'error'         => true,
                ]);
            } else {
                $this->session->setCustomerEmail($email);
            }
        } catch (\Exception $e) {
            $response->addData([
                'exception_message' => $e->getMessage(),
                'error'             => true,
            ]);
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }

    /**
     * Try to get customer by email.
     *
     * @param string $email
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    private function tryToGetCustomer($email)
    {
        try {
            $customer = $this->customerRepository->get($email);
        } catch (\Exception $e) {
            $customer = null;
        }

        return $customer;
    }
}
