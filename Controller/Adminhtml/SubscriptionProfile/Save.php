<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\Model\Session\Quote as SessionQuote;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\Processor\Request as RequestProcessor;

/**
 * Controller for creating subscription profiles.
 */
class Save extends AbstractSave
{
    /**
     * @var SessionQuote
     */
    private $sessionQuote;

    /**
     * Save constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param RequestProcessor $saveProcessor
     * @param SessionQuote $sessionQuote
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        RequestProcessor $saveProcessor,
        SessionQuote $sessionQuote
    ) {
        $this->sessionQuote = $sessionQuote;
        parent::__construct($context, $coreRegistry, $dataPersistor, $saveProcessor);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $errors = $this->getSaveProcessor()->processSave(
            $this->getRequest()->getParams()
        );
        if (empty($errors)) {
            $this->_getSession()->clearStorage();
            $this->sessionQuote->clearStorage();
            $this->messageManager->addSuccessMessage(
                __('Profile(s) was successfully created.')
            );
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::SubscriptionProfile_save'
        );
    }
}
