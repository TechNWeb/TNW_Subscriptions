<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile as ProfileModel;

/**
 * Class Edit - controller
 */
class Edit extends SubscriptionProfile
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param PageFactory $resultPageFactory
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        PageFactory $resultPageFactory,
        SubscriptionProfileRepositoryInterface $profileRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->profileRepository = $profileRepository;

        parent::__construct($context, $coreRegistry, $dataPersistor);
    }

    /**
     * Edit action.
     *
     * @return Page|Redirect|\Magento\Framework\App\ResponseInterface|ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $result = null;
        $profileId = $this->getRequest()->getParam('entity_id');
        $model = null;
        if ($profileId) {
            try {
                $model = $this->profileRepository->getById($profileId);
            } catch (\Exception $e) {
                return $this->redirectToList();
            }
        }
        $this->_coreRegistry->register('tnw_subscription_profile', $model, true);
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Subscription'));
        $resultPage->getConfig()->getTitle()->prepend($this->getSubscriptionTitle($model));

        return $resultPage;
    }

    /**
     * Returns to list view.
     *
     * @return Redirect
     */
    private function redirectToList()
    {
        $this->messageManager->addErrorMessage(__('This Subscription Profile no longer exists.'));
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
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
            'TNW_Subscriptions::SubscriptionProfile_edit'
        );
    }

    /**
     * Returns title for subscription profile.
     *
     * @param ProfileModel $model
     * @return \Magento\Framework\Phrase
     * @throws \Magento\Framework\Exception\LocalizedException
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
