<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;

class Create extends SubscriptionProfile
{
    /** @var PageFactory */
    protected $resultPageFactory;
    /** @var StepPool */
    protected $stepPool;

    /**
     * Create constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param StepPool $stepPool
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        StepPool $stepPool,
        PageFactory $resultPageFactory
    ) {
        $this->stepPool = $stepPool;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry, $dataPersistor);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $this->dataPersistor->clear(StepPool::PERSISTOR_STEP_PARAM_NAME);

        $currentStep = $this->getRequest()->getParam(
            StepPool::STEP_PARAM_NAME,
            StepPool::STEP_PARAM_TYPE_CUSTOMER
        );

        $resultPage = $this->resultPageFactory->create();

        if ($currentStep && $this->stepPool->checkStep($currentStep)) {
            $this->stepPool->setCurrentStep($currentStep);
            $resultPage->addHandle(
                'tnw_subscriptions_subscriptionprofile_create_' . $currentStep
            );
        }

        $resultPage->getConfig()->getTitle()->prepend(__("Creating Subscription(s)"));
        return $resultPage;
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::SubscriptionProfile_create'
        );
    }
}
