<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\BillingFrequency;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency;
use TNW\Subscriptions\Model\BillingFrequency;

class Delete extends \TNW\Subscriptions\Controller\Adminhtml\BillingFrequency
{

    /**
     * @var ProductBillingFrequency
     */
    private $productBillingFrequency;

    /**
     * @var BillingFrequency
     */
    private $billingFrequency;

    /**
     * Delete constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param ProductBillingFrequency $productBillingFrequency
     * @param BillingFrequency $billingFrequency
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ProductBillingFrequency $productBillingFrequency,
        BillingFrequency $billingFrequency
    ) {
        $this->productBillingFrequency = $productBillingFrequency;
        $this->billingFrequency = $billingFrequency;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Delete action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        $billingFrequency = $this->productBillingFrequency->isBillingFrequencyAllowedToProduct($id);
        // check if we know what should be deleted and it not have any associated products
        if ($id && empty($billingFrequency)) {
            try {
                // init model and delete
                $model = $this->billingFrequency->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the Billing Frequency.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__(
            'Cannot delete Billing Frequency. Remove all products from using this Billing Frequency first then try again.'
        ));
        // go to grid
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
            'TNW_Subscriptions::BillingFrequency_delete'
        );
    }
}
