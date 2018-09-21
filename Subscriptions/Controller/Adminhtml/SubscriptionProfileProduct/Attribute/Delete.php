<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

/**
 * Delete profile product attribute controller.
 */
class Delete extends \TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('attribute_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            $model = $this->_objectManager->create(Attribute::class);

            // entity type check
            $model->load($id);
            if ($model->getEntityTypeId() != $this->entityTypeId) {
                $this->messageManager->addError(__('We can\'t delete the attribute.'));
                return $resultRedirect->setPath('tnw_subscriptions/*/');
            }

            try {
                $model->delete();
                $this->messageManager->addSuccess(__('You deleted the product subscription profile attribute.'));
                return $resultRedirect->setPath('tnw_subscriptions/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath(
                    'tnw_subscriptions/*/edit',
                    ['attribute_id' => $this->getRequest()->getParam('attribute_id')]
                );
            }
        }
        $this->messageManager->addError(__('We can\'t find an attribute to delete.'));
        return $resultRedirect->setPath('tnw_subscriptions/*/');
    }
}
