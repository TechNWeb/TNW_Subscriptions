<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile as ProfileModel;

class Edit extends SubscriptionProfile
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context, $coreRegistry, $dataPersistor);
    }

    /**
     * Edit action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $result = null;
        $profileId = $this->getRequest()->getParam('entity_id');
        $model = $this->_objectManager->create(ProfileModel::class);

        if ($profileId) {
            $model->load($profileId);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Subscription Profile no longer exists.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->_coreRegistry->register('tnw_subscription_profile', $model);
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Subscription'));
        $resultPage->getConfig()->getTitle()->prepend($this->getSubscriptionTitle($model));

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
            'TNW_Subscriptions::SubscriptionProfile_edit'
        );
    }

    /**
     * Returns title for subscription profile.
     *
     * @param ProfileModel $model
     * @return \Magento\Framework\Phrase
     */
    private function getSubscriptionTitle(ProfileModel $model)
    {
        return __(
            sprintf(
                'Subscription (%s) for %s %s',
                $model->getLabel(),
                $model->getCustomer()->getFirstname(),
                $model->getCustomer()->getLastname()
            )
        );
    }
}
