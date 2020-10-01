<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Attribute;

use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @method \Magento\Framework\App\Request\Http getRequest()
 */
class Save extends \TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Attribute
{
    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\ValidatorFactory
     */
    protected $validatorFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    private $layoutFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory
     * @param \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\ValidatorFactory $validatorFactory
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\ValidatorFactory $validatorFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory);
        $this->productHelper = $productHelper;
        $this->attributeFactory = $attributeFactory;
        $this->validatorFactory = $validatorFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Zend_Validate_Exception
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $attributeSet = null;
            $attributeId = $this->getRequest()->getParam('attribute_id');
            $attributeCode = $this->getRequest()->getParam('attribute_code')
                ?: $this->generateCode($this->getRequest()->getParam('frontend_label')[0]);
            if (strlen($attributeCode) > 0) {
                $validatorAttrCode = new \Zend_Validate_Regex(['pattern' => '/^[a-z][a-z_0-9]{0,30}$/']);
                if (!$validatorAttrCode->isValid($attributeCode)) {
                    $this->messageManager->addError(
                        __(
                            'Attribute code "%1" is invalid. Please use only letters (a-z), ' .
                            'numbers (0-9) or underscore(_) in this field, first character should be a letter.',
                            $attributeCode
                        )
                    );

                    return $this->returnResult(
                        'tnw_subscriptions/*/edit',
                        ['attribute_id' => $attributeId, '_current' => true],
                        ['error' => true]
                    );
                }
            }
            $data['attribute_code'] = $attributeCode;

            //validate frontend_input
            if (isset($data['frontend_input'])) {
                /** @var $inputType \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\Validator */
                $inputType = $this->validatorFactory->create();
                if (!$inputType->isValid($data['frontend_input'])) {
                    foreach ($inputType->getMessages() as $message) {
                        $this->messageManager->addError($message);
                    }

                    return $this->returnResult(
                        'tnw_subscriptions/*/edit',
                        ['attribute_id' => $attributeId, '_current' => true],
                        ['error' => true]
                    );
                }
            }

            /* @var $model \Magento\Eav\Model\Attribute */
            $model = $this->attributeFactory->create();

            if ($attributeId) {
                $model->load($attributeId);
                if (!$model->getId()) {
                    $this->messageManager->addError(__('This attribute no longer exists.'));

                    return $this->returnResult('tnw_subscriptions/*/', [], ['error' => true]);
                }
                // entity type check
                if ($model->getEntityTypeId() != $this->entityTypeId) {
                    $this->messageManager->addError(__("We can't update the attribute."));
                    $this->_session->setAttributeData($data);

                    return $this->returnResult('tnw_subscriptions/*/', [], ['error' => true]);
                }

                $data['attribute_code'] = $model->getAttributeCode();
                $data['is_user_defined'] = $model->getIsUserDefined();
                $data['frontend_input'] = $model->getFrontendInput();
            } else {
                /**
                 * @todo add to helper and specify all relations for properties
                 */
                $data['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
                    $data['frontend_input']
                );
                $data['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
                    $data['frontend_input']
                );
            }

            if ($model->getIsUserDefined() === null || $model->getIsUserDefined() != 0) {
                $data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);
            }

            $defaultValueField = $model->getDefaultValueByInput($data['frontend_input']);
            if ($defaultValueField) {
                $data['default_value'] = $this->getRequest()->getParam($defaultValueField);
            }

            $model->addData($data);

            if (!$attributeId) {
                $model->setEntityTypeId($this->entityTypeId);
                $model->setIsUserDefined(1);
            }

            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved the Subscription Profile attribute.'));

                $this->_session->setAttributeData(false);
                if ($this->getRequest()->getParam('back', false)) {
                    return $this->returnResult(
                        'tnw_subscriptions/*/edit',
                        ['attribute_id' => $model->getId(), '_current' => true],
                        ['error' => false]
                    );
                }

                return $this->returnResult('tnw_subscriptions/*/', [], ['error' => false]);
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_session->setAttributeData($data);

                return $this->returnResult(
                    'tnw_subscriptions/*/edit',
                    ['attribute_id' => $attributeId, '_current' => true],
                    ['error' => true]
                );
            }
        }

        return $this->returnResult('tnw_subscriptions/*/', [], ['error' => true]);
    }

    /**
     * @param string $path
     * @param array $params
     * @param array $response
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Backend\Model\View\Result\Redirect
     */
    private function returnResult($path = '', array $params = [], array $response = [])
    {
        if ($this->getRequest()->isAjax()) {
            /** @var \Magento\Framework\View\Layout $layout */
            $layout = $this->layoutFactory->create();
            $layout->initMessages();

            $response['messages'] = [$layout->getMessagesBlock()->getGroupedHtml()];
            $response['params'] = $params;

            return $this->resultFactory
                ->create(ResultFactory::TYPE_JSON)
                ->setData($response);
        }

        return $this->resultFactory
            ->create(ResultFactory::TYPE_REDIRECT)
            ->setPath($path, $params);
    }
}
