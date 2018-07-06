<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Attribute;

class Delete extends \TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Attribute
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('attribute_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            $model = $this->_objectManager->create(\Magento\Eav\Model\Attribute::class);

            // entity type check
            $model->load($id);
            if ($model->getEntityTypeId() != $this->entityTypeId) {
                $this->messageManager->addError(__("We can't delete the attribute."));

                return $resultRedirect->setPath('tnw_subscriptions/*/');
            }

            try {
                $model->delete();
                $this->messageManager->addSuccess(__('You deleted the Subscription Profile attribute.'));

                return $resultRedirect->setPath('tnw_subscriptions/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath(
                    'tnw_subscriptions/*/edit',
                    ['attribute_id' => $this->getRequest()->getParam('attribute_id')]
                );
            }
        }
        $this->messageManager->addError(__("We can't find an attribute to delete."));

        return $resultRedirect->setPath('tnw_subscriptions/*/');
    }
}
