<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;

class Create extends Action
{
    /** @var PageFactory */
    protected $resultPageFactory;
    /** @var StepPool */
    protected $stepPool;

    /**
     * Create constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param StepPool $stepPool
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StepPool $stepPool
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->stepPool = $stepPool;
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
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

    /**
     * @return \TNW\Subscriptions\Model\Backend\Session\Quote
     */
    protected function _getSession()
    {
        return $this->_objectManager->get('TNW\Subscriptions\Model\Backend\Session\Quote');
    }
}
