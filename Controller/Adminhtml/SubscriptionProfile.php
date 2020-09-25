<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Abstract class for profile admin actions.
 */
abstract class SubscriptionProfile extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'TNW_Subscriptions::top_level';

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * SubscriptionProfile constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu('TNW_Subscriptions::tnw_subscriptions_subscription_profile')
            ->addBreadcrumb(__('TNW'), __('TNW'))
            ->addBreadcrumb(
                __('Subscription Profile'),
                __('Subscription Profile')
            );

        return $resultPage;
    }

    /**
     * @return QuoteSessionInterface
     */
    protected function _getSession()
    {
        return $this->_objectManager->get(QuoteSessionInterface::class);
    }

    /**
     * @return \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile
     */
    protected function getSubCreateModel()
    {
        return $this->_objectManager->get(
            \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile::class
        );
    }

    /**
     * Clear session data
     */
    protected function clearSessionData()
    {
        $this->_getSession()->clearStorage();
        //remove step param from session
        $this->dataPersistor->clear(StepPool::PERSISTOR_STEP_PARAM_NAME);
    }
}
