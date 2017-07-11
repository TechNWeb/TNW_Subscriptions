<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create;

use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Account;

class Process extends Create
{
    /**
     * Errors list
     *
     * @var array
     */
    private $errors = [];

    /**
     * Start order create action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->resetErrors();

        $this->processRequestData();

        $currentStep = $this->getRequest()->getParam(
            StepPool::STEP_PARAM_NAME,
            StepPool::STEP_PARAM_TYPE_CUSTOMER
        );

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!empty($this->errors)){

            foreach ($this->errors as $error){
                $this->messageManager->addErrorMessage($error);
            }

            $currentStep = $this->stepPool->setCurrentStep($currentStep)->getPrevStep();
        }

        $redirectParams = [
            StepPool::STEP_PARAM_NAME => $currentStep,
        ];

        $redirectParams = array_merge($redirectParams, $this->getAdditionalParams($currentStep));

        return $resultRedirect->setPath(
            'tnw_subscriptions/subscriptionprofile/create',
            $redirectParams
        );
    }

    private function processRequestData()
    {
        $requestData = $this->getRequest()->getParams();

        $this->processStoreData($requestData);

        $this->processCustomerData($requestData);

        $this->processCurrencyData($requestData);

        $this->processAccountData($requestData);

        $this->processShippingMethods($requestData);

        $this->processPaymentAndBillingData($requestData);
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
    }

    /**
     * Process post data from currency form.
     *
     * @param $data
     */
    private function processCurrencyData($data)
    {
        if (isset($data['currency_id'])){
            $this->_getSession()->setCurrencyId($data['currency_id']);
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
            $customerAddressId = !empty($data['account']['customer_address_id'])
                ? $data['account']['customer_address_id']
                : null;

            $result = $this->getSubCreateModel()->setShippingAddress($data['account'], $customerAddressId);

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
    private function processPaymentAndBillingData($data)
    {
        if (isset($data['billing'])){
            $customerAddressId = !empty($data['billing']['customer_address_id'])
                ? $data['billing']['customer_address_id']
                : null;

            $result = $this->getSubCreateModel()->setBillingAddress($data['billing'], $customerAddressId);

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
        }

        return $additionalParams;
    }
}
