<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create;

use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Account;

class Process extends SubscriptionProfile
{
    /**
     * Start order create action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->processRequestData();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */

        $currentStep = $this->getRequest()->getParam(
            StepPool::STEP_PARAM_NAME,
            StepPool::STEP_PARAM_TYPE_CUSTOMER
        );

        $resultRedirect = $this->resultRedirectFactory->create();

        $redirectParams = [
            StepPool::STEP_PARAM_NAME => $currentStep,
        ];

        $redirectParams = array_merge($redirectParams, $this->getAdditionalParams($currentStep));

        return $resultRedirect->setPath(
            'tnw_subscriptions/subscriptionprofile/create',
            $redirectParams
        );
    }

    protected function processRequestData()
    {
        $requestData = $this->getRequest()->getParams();

        $this->processStoreData($requestData);

        $this->processCustomerData($requestData);

        $this->processCurrencyData($requestData);

        $this->processAccountData($requestData);

        $this->processShippingAndPaymentData($requestData);
    }

    protected function processStoreData($data)
    {
        if (isset($data['store_id'])){
            $this->_getSession()->setStoreId($data['store_id']);
        }
    }


    protected function processCustomerData($data)
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

    protected function processCurrencyData($data)
    {
        if (isset($data['currency_id'])){
            $this->_getSession()->setCurrencyId($data['currency_id']);
        }
    }

    protected function processAccountData($data)
    {
        if (isset($data['account'])){
            $this->_getSession()->setShippingAddressData($data['account']);
        }
    }

    protected function processShippingAndPaymentData($data)
    {
        //TODO add logic to process post data
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
        if ($currentStep == StepPool::STEP_PARAM_TYPE_ACCOUNT_INFORMATION) {
            $additionalParams = [
                Account::FORM_DATA_KEY => Account::FORM_DATA_VALUE,
            ];
        }

        return $additionalParams;
    }
}
