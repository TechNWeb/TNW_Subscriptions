<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\BillingFrequency;

use Magento\Framework\App\Request\DataPersistorInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;

/**
 * Class Edit - controller for edit billing frequency
 */
class Edit extends \TNW\Subscriptions\Controller\Adminhtml\BillingFrequency
{
    /**
     * Result page factory.
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     * Data persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        DataPersistorInterface $dataPersistor
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->dataPersistor = $dataPersistor;

        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->dataPersistor->clear('tnw_' . ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID);

        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('TNW\Subscriptions\Model\BillingFrequency');

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Billing Frequency no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('tnw_subscriptions_billingfrequency', $model);
        $this->dataPersistor->set('tnw_' . ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID, $id);

        // 5. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Billing Frequency') : __('New Billing Frequency'),
            $id ? __('Edit Billing Frequency') : __('New Billing Frequency')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Billing Frequencys'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getTitle() : __('New Billing Frequency')
        );

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
            'TNW_Subscriptions::BillingFrequency_edit'
        );
    }
}
