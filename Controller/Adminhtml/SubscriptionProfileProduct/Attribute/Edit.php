<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute;

use Magento\Backend\Model\Session;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\Product\Attribute\Frontend\Inputtype\Presentation;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Profile product attribute edit page controller.
 */
class Edit extends \TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute
{
    /**
     * @var Presentation
     */
    protected $presentation;

    /**
     * Edit constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param Presentation $presentation
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        Presentation $presentation
    ) {
        $this->presentation = $presentation;
        parent::__construct($context, $coreRegistry, $resultPageFactory);
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('attribute_id');
        /** @var $model Attribute */
        $model = $this->_objectManager->create(
            Attribute::class
        )->setEntityTypeId(
            $this->entityTypeId
        );
        if ($id) {
            $model->load($id);

            if (!$model->getId()) {
                $this->messageManager->addError(__('This attribute no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('catalog/*/');
            }

            // entity type check
            if ($model->getEntityTypeId() != $this->entityTypeId) {
                $this->messageManager->addError(__('This attribute cannot be edited.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('catalog/*/');
            }
        }

        // set entered data if was error when we do save.
        $data = $this->_objectManager->get(Session::class)->getAttributeData(true);
        $model->setFrontendInput($this->presentation->getPresentationInputType($model));
        if (!empty($data)) {
            $model->addData($data);
        }
        $attributeData = $this->getRequest()->getParam('attribute');
        if (!empty($attributeData) && $id === null) {
            $model->addData($attributeData);
        }

        $this->coreRegistry->register('entity_attribute', $model);

        $item = $id ? __('Edit Product Subscription Profile Attribute')
            : __('New Product Subscription Profile Attribute');

        $resultPage = $this->createActionPage($item);
        $resultPage->getConfig()->getTitle()->prepend($id ? $model->getName()
            : __('New Product Subscription Profile Attribute'));

        return $resultPage;
    }
}
