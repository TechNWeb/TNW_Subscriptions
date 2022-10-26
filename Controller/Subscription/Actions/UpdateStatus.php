<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\Actions;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\Context;
use TNW\Subscriptions\Block\Subscription\History;
use TNW\Subscriptions\Block\Subscription\Summary\Overview;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Controller\Subscription\Items;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\UpdateStatus as UpdateStatusModel;

/**
 * Controller for subscription history at customer account dashboard.
 */
class UpdateStatus extends \Magento\Framework\App\Action\Action
{
    /**
     * Model for update status.
     *
     * @var UpdateStatusModel
     */
    private $updateStatusModel;

    /**
     * Profile status data source
     *
     * @var ProfileStatus
     */
    private $statusSource;

    /**
     * Subscription items at customer account
     *
     * @var Items
     */
    private $subscriptionItems;

    /**
     * UpdateStatus constructor.
     * @param Context $context
     * @param ProfileStatus $statusSource
     * @param Items $subscriptionItems
     * @param UpdateStatusModel $updateStatusModel
     */
    public function __construct(
        Context $context,
        ProfileStatus $statusSource,
        Items $subscriptionItems,
        UpdateStatusModel $updateStatusModel
    ) {
        $this->updateStatusModel = $updateStatusModel;
        $this->statusSource = $statusSource;
        $this->subscriptionItems = $subscriptionItems;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getParam('entity_id');
        $newStatus = $this->getRequest()->getParam('status');

        try {
            if (!$this->subscriptionItems->canViewSubscriptionById($profileId)) {
                throw new \Magento\Framework\Exception\NoSuchEntityException();
            }
            $model = $this->updateStatusModel->updateStatus(
                $profileId,
                $newStatus,
                $this->getRequest()->getParam('suspension_type') == 'billing_cycles'
                    ? $this->getRequest()->getParam('cycles_count')
                    : 0,
                true
            );
            if ($model != null && $this->_request->isAjax()) {
                $this->messageManager->addComplexSuccessMessage(
                    'addHtmlMessage',
                    [
                        'subs_label' => $model->getLabel(),
                        'subs_url' => $this->_url->getUrl(
                            'tnw_subscriptions/subscription/edit',
                            [
                                'entity_id' => $profileId
                            ]
                        ),
                        'subs_status' => $this->statusSource->getLabelByValue($newStatus)->getText()
                    ]
                );
                return $this->getResponse()->representJson('{"error":"false"}');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            if ($this->_request->isAjax()) {
                return $this->getResponse()->representJson('{"error":"true"}');
            }
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
        $redirect = $this->getRequest()->getParam('redirect');
        switch ($redirect) {
            case History::REDIRECT:
                $resultRedirect->setPath('*/subscription/history');
                break;
            case Overview::REDIRECT:
                $resultRedirect->setPath(
                    '*/subscription/edit',
                    ['entity_id' => $this->getRequest()->getParam('entity_id')]
                );
                break;
        }

        return $resultRedirect;
    }
}
