<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Helper\Product;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\ValidatorFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Model\Product\Attribute\Frontend\Inputtype\Presentation;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\FormData;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute;

/**
 * Profile product attribute save controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Attribute
{
    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var Product
     */
    private $productHelper;

    /**
     * @var AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var ValidatorFactory
     */
    private $validatorFactory;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var mixed
     */
    private $presentation;

    /**
     * @var FormData|null
     */
    private $formDataSerializer;

    /**
     * Save constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param AttributeFactory $attributeFactory
     * @param ValidatorFactory $validatorFactory
     * @param FilterManager $filterManager
     * @param Product $productHelper
     * @param LayoutFactory $layoutFactory
     * @param Presentation|null $presentation
     * @param FormData|null $formDataSerializer
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        AttributeFactory $attributeFactory,
        ValidatorFactory $validatorFactory,
        FilterManager $filterManager,
        Product $productHelper,
        LayoutFactory $layoutFactory,
        Presentation $presentation = null,
        FormData $formDataSerializer = null
    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory);
        $this->filterManager = $filterManager;
        $this->productHelper = $productHelper;
        $this->attributeFactory = $attributeFactory;
        $this->validatorFactory = $validatorFactory;
        $this->layoutFactory = $layoutFactory;
        $this->presentation = $presentation ?: ObjectManager::getInstance()->get(Presentation::class);
        $this->formDataSerializer = $formDataSerializer
            ?: ObjectManager::getInstance()->get(FormData::class);
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Zend_Validate_Exception
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            try {
                $optionData = $this->formDataSerializer
                    ->unserialize($this->getRequest()->getParam('serialized_options', '[]'));
            } catch (\InvalidArgumentException $e) {
                $message = __("The attribute couldn't be saved due to an error. Verify your information and try again. "
                    . "If the error persists, please try again later.");
                $this->messageManager->addErrorMessage($message);
                return $this->returnResult('*/*/edit', ['_current' => true], ['error' => true]);
            }
            $data = array_replace_recursive(
                $data,
                $optionData
            );
            $attributeId = $this->getRequest()->getParam('attribute_id');

            /** @var $model \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
            $model = $this->attributeFactory->create()->load($attributeId);
            if (!$model->getId() && $attributeId) {
                $this->messageManager->addErrorMessage(__('This attribute no longer exists.'));
                return $this->returnResult('tnw_subscriptions/*/', [], ['error' => true]);
            }

            if ($model->getId()) {
                // entity type check
                if ($model->getEntityTypeId() != $this->entityTypeId) {
                    $this->messageManager->addErrorMessage(__('We can\'t update the attribute.'));
                    $this->_session->setAttributeData($data);

                    return $this->returnResult('tnw_subscriptions/*/', [], ['error' => true]);
                }

                $data['attribute_code'] = $model->getAttributeCode();
                $data['is_user_defined'] = $model->getIsUserDefined();
                $data['frontend_input'] = $data['frontend_input'] ?? $model->getFrontendInput();
            } else {
                $attributeCode = $this->getRequest()->getParam('attribute_code')
                    ?: $this->generateCode($this->getRequest()->getParam('frontend_label')[0]);

                if (!\Zend_Validate::is($attributeCode, 'Regex', ['pattern' => '/^[a-z][a-z_0-9]{0,30}$/'])) {
                    $this->messageManager->addErrorMessage(
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

                $data['attribute_code'] = $attributeCode;

                //validate frontend_input
                if (isset($data['frontend_input'])) {
                    /** @var $inputType \Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype\Validator */
                    $inputType = $this->validatorFactory->create();
                    if (!$inputType->isValid($data['frontend_input'])) {
                        foreach ($inputType->getMessages() as $message) {
                            $this->messageManager->addErrorMessage($message);
                        }

                        return $this->returnResult(
                            'tnw_subscriptions/*/edit',
                            ['attribute_id' => $attributeId, '_current' => true],
                            ['error' => true]
                        );
                    }
                }

                $data['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
                    $data['frontend_input']
                );
                $data['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
                    $data['frontend_input']
                );

                if (strcasecmp($data['frontend_input'], 'multiselect') === 0) {
                    $data['source_model'] = \Magento\Eav\Model\Entity\Attribute\Source\Table::class;
                }
            }
            $data = $this->presentation->convertPresentationDataToInputType($data);
            if ($model->getIsUserDefined() === null || $model->getIsUserDefined() != 0) {
                $data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);
            }

            $defaultValueField = $model->getDefaultValueByInput($data['frontend_input']);
            if ($defaultValueField) {
                $data['default_value'] = $this->getRequest()->getParam($defaultValueField);
            }

            if (!$model->getIsUserDefined() && $model->getId()) {
                // Unset attribute field for system attributes
                unset($data['apply_to']);
            }

            $model->addData($data);

            if (!$attributeId) {
                $model->setEntityTypeId($this->entityTypeId);
                $model->setIsUserDefined(1);
            }

            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved the product subscription profile attribute.'));
                $this->_session->setAttributeData(false);
                $result = $this->returnResult('tnw_subscriptions/*/', [], ['error' => false]);
                if ($this->getRequest()->getParam('back', false)) {
                    $result = $this->returnResult(
                        'tnw_subscriptions/*/edit',
                        ['attribute_id' => $model->getId(), '_current' => true],
                        ['error' => false]
                    );
                }

                return $result;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_session->setAttributeData($data);

                return $this->returnResult(
                    'catalog/*/edit',
                    ['attribute_id' => $attributeId, '_current' => true],
                    ['error' => true]
                );
            }
        }

        return $this->returnResult('tnw_subscriptions/*/', [], ['error' => true]);
    }

    /**
     * Build response depending on request type.
     *
     * @param string $path
     * @param array $params
     * @param array $response
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Backend\Model\View\Result\Redirect
     */
    private function returnResult($path = '', array $params = [], array $response = [])
    {
        if ($this->isAjax()) {
            $layout = $this->layoutFactory->create();
            $layout->initMessages();

            $response['messages'] = [$layout->getMessagesBlock()->getGroupedHtml()];
            $response['params'] = $params;

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath($path, $params);
    }

    /**
     * Define whether request is Ajax.
     *
     * @return boolean
     */
    private function isAjax()
    {
        return $this->getRequest()->getParam('isAjax');
    }
}
