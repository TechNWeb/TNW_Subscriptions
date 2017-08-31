<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\StatusManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Action To update status in Subscription Profile
 */
class UpdateStatus extends Action
{
    /**
     * Repository profile
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * The Manager that define logic of status change on Subscription Profile
     *
     * @var StatusManager
     */
    private $statusManager;

    /**
     * Profile status data source
     *
     * @var ProfileStatus
     */
    private $statusSource;
    
    /**
     * @param Context $context
     * @param SubscriptionProfileRepository $profileRepository
     * @param StatusManager $statusManager
     * @param ProfileStatus $statusSource
     */
    public function __construct(
        Context $context,
        SubscriptionProfileRepository $profileRepository,
        StatusManager $statusManager,
        ProfileStatus $statusSource
    ) {
        $this->profileRepository = $profileRepository;
        $this->statusManager = $statusManager;
        $this->statusSource = $statusSource;
        parent::__construct($context);
    }

    /**
     * Update status in Subscription Profile
     *
     * @return Redirect
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getParam('entity_id');
        $newStatus = $this->getRequest()->getParam('status');

        try {
            /* @var SubscriptionProfile $model */
            $model = $this->profileRepository->getById($profileId);

            if (!$this->statusManager->canChangeStatus($model, $newStatus)) {
                $this->messageManager->addErrorMessage(
                    __('Status can not be change to "%1"', $this->statusSource->getLabelByValue($newStatus))
                );
                return $this->getRedirect();
            }

            $model->setStatus($newStatus);
            $this->profileRepository->save($model);
            $this->messageManager->addSuccessMessage(__(
                'Status successfully changed to "%1"',
                $this->statusSource->getLabelByValue($newStatus)
            ));

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->getRedirect();
        }

        return $this->getRedirect();
    }

    /**
     * Retrieve redirect model
     *
     * @return Redirect
     */
    private function getRedirect()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('entity_id')]);
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
}