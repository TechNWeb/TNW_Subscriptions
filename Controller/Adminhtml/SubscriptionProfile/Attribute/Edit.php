<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Attribute;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Edit - controller
 */
class Edit extends \TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Attribute
{
    /**
     * @var mixed|null
     */
    private $config = null;

    /**
     * Edit constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled("Magento_PageBuilder")) {
            $this->config = $objectManager->get(\Magento\PageBuilder\Model\Config::class);
        }
        parent::__construct($context, $coreRegistry, $resultPageFactory);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('attribute_id');
        /** @var $model \Magento\Eav\Model\Attribute */
        $model = $this->_objectManager->create(
            \Magento\Eav\Model\Attribute::class
        )->setEntityTypeId(
            $this->entityTypeId
        );
        if ($id) {
            $model->load($id);

            if (!$model->getId()) {
                $this->messageManager->addError(__('This attribute no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('tnw_subscriptions/*/');
            }

            // entity type check
            if ($model->getEntityTypeId() != $this->entityTypeId) {
                $this->messageManager->addError(__('This attribute cannot be edited.'));
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('tnw_subscriptions/*/');
            }
        }

        // set entered data if was error when we do save
        $data = $this->_objectManager->get(\Magento\Backend\Model\Session::class)->getAttributeData(true);
        $inputType = $model->getFrontendInput();
        if ($inputType === 'textarea' && $model->getIsWysiwygEnabled()) {
            if ($this->config && $model->getIsPagebuilderEnabled() && $this->config->isEnabled()) {
                $model->setFrontendInput('pagebuilder');
            } else {
                $model->setFrontendInput('texteditor');
            }
        }
        if ($model->getFrontendInput() == 'textarea' && $model->getIsWysiwygEnabled()) {
            $model->setFrontendInput('texteditor');
        }

        if (!empty($data)) {
            $model->addData($data);
        }
        $attributeData = $this->getRequest()->getParam('attribute');
        if (!empty($attributeData) && $id === null) {
            $model->addData($attributeData);
        }

        $this->_coreRegistry->register('entity_attribute', $model);

        $item = $id ? __('Edit Subscription Profile Attribute') : __('New Subscription Profile Attribute');

        $resultPage = $this->createActionPage($item);
        $resultPage->getConfig()->getTitle()->prepend($id
            ? $model->getName()
            : __('New Subscription Profile Attribute'));

        return $resultPage;
    }
}
