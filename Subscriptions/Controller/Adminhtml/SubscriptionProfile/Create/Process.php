<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Account;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\ShippingAndBilling;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Store;
use Magento\Framework\Controller\Result\JsonFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;

class Process extends Create
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        StepPool $stepPool,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context, $coreRegistry, $dataPersistor, $stepPool,
            $resultPageFactory);
    }


    /**
     * Errors list
     *
     * @var array
     */
    private $errors = [];

    /**
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $this->resetErrors();

        $this->processRequestData();

        $currentStep = $this->getRequest()->getParam(
            StepPool::STEP_PARAM_NAME,
            StepPool::STEP_PARAM_TYPE_CUSTOMER
        );

        $this->processBackActions($currentStep);

        $this->processRequestData();

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $result = $this->resultRedirectFactory->create();

        if ($this->getRequest()->getParam('isAjax', false)) {
            $result = $this->resultJsonFactory->create();
            $result->setData(
                $this->getJsonResponse()
            );
        } else {
            if (!empty($this->errors)) {

                foreach ($this->errors as $error) {
                    $this->messageManager->addErrorMessage($error);
                }

                $currentStep = $this->stepPool->setCurrentStep($currentStep)->getPrevStep();
            }

            $redirectParams = [
                StepPool::STEP_PARAM_NAME => $currentStep,
            ];

            $redirectParams = array_merge($redirectParams, $this->getAdditionalParams($currentStep));

            $result->setPath(
                'tnw_subscriptions/subscriptionprofile/create',
                $redirectParams
            );
        }

        return $result;
    }

    private function processRequestData()
    {
        $requestData = $this->getRequest()->getParams();

        $this->processStoreData($requestData);

        $this->processCustomerData($requestData);

        $this->processCurrencyData($requestData);

        $this->processAccountData($requestData);

        $this->processShippingMethods($requestData);

        $this->processBillingData($requestData);

        $this->processPaymentData($requestData);

        $this->getSubCreateModel()->recollectSubscriptions();
    }

    /**
     * Process post data from store form.
     *
     * @param $data
     */
    private function processStoreData($data)
    {
        if (isset($data['store_id'])){
            $this->_getSession()->setStoreId($data['store_id']);
            $this->getSubCreateModel()->deleteQuoteIfStoreChanged();
        }
    }

    /**
     * Process request data from "choose customer" page.
     *
     * @param $data
     */
    private function processCustomerData($data)
    {
        if (isset($data['customer_id'])){
            $this->_getSession()->setCustomerId($data['customer_id']);
            $this->_getSession()->setCreateNewCustomer(null);
        }

        if (isset($data['create_new_customer'])){
            $this->_getSession()->setCreateNewCustomer($data['create_new_customer']);
            $this->_getSession()->setCustomerId(null);
        }

        if (isset($data['customer_id']) || isset($data['create_new_customer'])) {
            $this->getSubCreateModel()->changeCustomerInQuote();
        }
    }

    /**
     * Process post data from currency form.
     *
     * @param $data
     */
    private function processCurrencyData($data)
    {
        if (isset($data['currency_id'])) {
            $this->_getSession()->setCurrencyId($data['currency_id']);

            $result = $this->getSubCreateModel()->setCurrency($data['currency_id']);

            $this->checkProcessResult($result);
        }
    }

    /**
     * Process post data from account form.
     *
     * @param $data
     */
    private function processAccountData($data)
    {
        if (isset($data['account'])){
            $email = !empty($data['account']['email']) ? $data['account']['email'] : null;
            $group = !empty($data['account']['group']) ? $data['account']['group'] : null;
            $this->_getSession()->setCustomerEmail($email);
            $this->_getSession()->setCustomerGroup($group);
        }

        if (!empty($data['shipping_address']) && !empty($data['shipping_info'])) {
            $address = array_merge($data['shipping_address'], $data['shipping_info']);
            $customerAddressId = !empty($data['shipping_address']['customer_address_id'])
                ? $data['shipping_address']['customer_address_id']
                : null;

            $result = $this->getSubCreateModel()->setShippingAddress($address, $customerAddressId);

            $this->checkProcessResult($result);
        }
    }


    private function processShippingMethods($data)
    {
        if (isset($data['shipping_methods'])){

            $result = $this->getSubCreateModel()->setShippingMethods($data['shipping_methods']);

            $this->checkProcessResult($result);
        }
    }

    /**
     * Process post data from payment and billing form.
     *
     * @param $data
     */
    private function processBillingData($data)
    {
        $address = isset($data['billing_address']) ? $data['billing_address'] : [];
        $info = isset($data['billing_info']) ? $data['billing_info'] : [];
        $billing = array_merge($address, $info);
        if (!empty($billing)){
            $customerAddressId = !empty($billing['customer_address_id'])
                ? $billing['customer_address_id']
                : null;

            $result = $this->getSubCreateModel()->setBillingAddress($billing, $customerAddressId);

            $this->checkProcessResult($result);
        }
    }

    /**
     * Process post data from payment and billing form.
     *
     * @param $data
     */
    private function processPaymentData($data)
    {
        if (isset($data['payment'])){
            $result = [];

            foreach ($data['payment'] as $code => $methodData){
                if ($methodData['method']){
                    $result = $this->getSubCreateModel()->setPaymentMethod($code);
                    break;
                }
            }

            $this->checkProcessResult($result);
        }
    }

    /**
     *  Adds to errors array errors from process request data methods.
     *
     * @param [] $result
     */
    private function checkProcessResult($result)
    {
        if (is_array($result)){
            $this->errors = array_merge($this->errors, $result);
        }
    }

    /**
     * Resets errors array.
     */
    private function resetErrors()
    {
        $this->errors = [];
    }

    /**
     * Returns additional request params.
     *
     * @param string $currentStep
     * @return array
     */
    private function getAdditionalParams($currentStep)
    {
        $additionalParams = [];
        if ($currentStep === StepPool::STEP_PARAM_TYPE_ACCOUNT_INFORMATION) {
            $additionalParams = [
                Account::FORM_DATA_KEY => Account::FORM_DATA_VALUE,
            ];
        } elseif ($currentStep === StepPool::STEP_PARAM_TYPE_SHIPPING_BILLING) {
            $additionalParams = [
                ShippingAndBilling::FORM_DATA_KEY => ShippingAndBilling::FORM_DATA_VALUE,
            ];
        } elseif ($currentStep === StepPool::STEP_PARAM_TYPE_STORE) {
            $additionalParams = [
                Store::FORM_DATA_KEY => Store::FORM_DATA_VALUE,
            ];
        } elseif ($currentStep === StepPool::STEP_PARAM_TYPE_PAYMENT) {
            $additionalParams = [
                Payment::FORM_DATA_KEY => Payment::FORM_DATA_VALUE,
            ];
        }

        return $additionalParams;
    }

    /**
     * @return array
     */
    private function getJsonResponse()
    {
        $result = [
            'data' => [],
            'error' => false
        ];

        if (!empty($this->errors)){
            $result = [
                'error_messages' => $this->errors,
                'error' => true
            ];
        }

        return $result;
    }

    /**
     * Process data if 'Back' button was pressed.
     *
     * @param string $currentStep
     * @return void
     */
    private function processBackActions($currentStep)
    {
        $back = $this->getRequest()->getParam('back', 0);

        if ($back) {
            if ($currentStep === StepPool::STEP_PARAM_TYPE_ACCOUNT_INFORMATION) {
                $this->getSubCreateModel()->clearAccountStepData();
            } elseif ($currentStep === StepPool::STEP_PARAM_TYPE_SHIPPING_BILLING) {
                $this->getSubCreateModel()->cleaBillingStepData();
            }
        }
    }
}
