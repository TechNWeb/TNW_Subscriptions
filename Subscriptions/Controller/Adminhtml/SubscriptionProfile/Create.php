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

/**
 * Create subscription profile.
 */
class Create extends SubscriptionProfile
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var StepPool
     */
    protected $stepPool;

    /**
     * @param Context                $context
     * @param Registry               $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param StepPool               $stepPool
     * @param PageFactory            $resultPageFactory
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
            StepPool::STEP_PARAM_TYPE_STORE
        );

        $resultPage = $this->resultPageFactory->create();
        $pageTitle = __("Creating Subscription(s)");

        if ($currentStep && $this->stepPool->isAvailable($currentStep)) {
            $this->stepPool->setCurrentStep($currentStep);
            $resultPage->addHandle(
                'tnw_subscriptions_subscriptionprofile_create_' . $this->stepPool->getCurrentStep()
            );
            $pageTitle .= $this->stepPool->getCurrentStepTitle();
        }

        $resultPage->getConfig()->getTitle()->prepend($pageTitle);

        return $resultPage;
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TNW_Subscriptions::SubscriptionProfile_create');
    }
}
