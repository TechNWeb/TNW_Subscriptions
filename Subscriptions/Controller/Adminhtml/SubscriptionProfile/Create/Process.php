<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Registry;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\AbstractSave;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\Processor\Request as RequestProcessor;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Account;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\ShippingAndBilling;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Store;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;

/**
 * Process subscription profile data during creation.
 */
class Process extends AbstractSave
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Step pool.
     *
     * @var StepPool
     */
    private $stepPool;

    /**
     * Process constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param RequestProcessor $saveProcessor
     * @param JsonFactory $resultJsonFactory
     * @param StepPool $stepPool
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        RequestProcessor $saveProcessor,
        JsonFactory $resultJsonFactory,
        StepPool $stepPool
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->stepPool = $stepPool;

        parent::__construct($context, $coreRegistry, $dataPersistor, $saveProcessor);
    }

    /**
     * @return Json|Redirect
     */
    public function execute()
    {
        $currentStep = $this->getRequest()->getParam(
            StepPool::STEP_PARAM_NAME,
            StepPool::STEP_PARAM_TYPE_STORE
        );
        $this->processBackActions($currentStep);
        $errors = $this->processRequestData();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $result = $this->resultRedirectFactory->create();

        if ($this->getRequest()->getParam('isAjax', false)) {
            $result = $this->resultJsonFactory->create();
            $result->setData($this->getJsonResponse($errors));
        } else {
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->messageManager->addErrorMessage($error);
                }
                $currentStep = $this->stepPool->setCurrentStep($currentStep)->getPrevStep();
            }
            $redirectParams = [StepPool::STEP_PARAM_NAME => $currentStep];
            $redirectParams = array_merge($redirectParams, $this->getAdditionalParams($currentStep));
            $result->setPath('tnw_subscriptions/subscriptionprofile/create', $redirectParams);
        }

        return $result;
    }

    /**
     * Processing save post data. Returns list of errors.
     *
     * @return array
     */
    private function processRequestData()
    {
        $result = $this->getSaveProcessor()->processSave(
            $this->getRequest()->getParams()
        );
        $this->getSubCreateModel()->recollectSubscriptions();

        return $result;
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
        if ($currentStep === StepPool::STEP_PARAM_TYPE_SHIPPING) {
            $additionalParams = [
                Account::FORM_DATA_KEY => Account::FORM_DATA_VALUE,
            ];
        } elseif ($currentStep === StepPool::STEP_PARAM_TYPE_BILLING) {
            $additionalParams = [
                ShippingAndBilling::FORM_DATA_KEY => ShippingAndBilling::FORM_DATA_VALUE,
            ];
        } elseif ($currentStep === StepPool::STEP_PARAM_TYPE_STORE) {
            $additionalParams = [
                Store::FORM_DATA_KEY => Store::FORM_DATA_VALUE,
            ];
        } elseif ($currentStep === StepPool::STEP_PARAM_TYPE_PAYMENT) {
            $additionalParams = [
                Payment::PAYMENT_FORM_DATA_KEY => Payment::PAYMENT_FORM_DATA_VALUE,
            ];
        }

        return $additionalParams;
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

        if (1 == $back && $currentStep === StepPool::STEP_PARAM_TYPE_BILLING) {
            $this->getSubCreateModel()->clearBillingStepData();
        }
    }
}
