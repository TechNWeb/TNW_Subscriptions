<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute;

use Magento\Framework\DataObject;

/**
 * Validation controller for profile product attributes before save,
 */
class Validate extends \TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute
{
    const DEFAULT_MESSAGE_KEY = 'message';

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var array
     */
    private $multipleAttributeList;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \Magento\Eav\Model\AttributeFactory
     */
    private $attributeFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param array $multipleAttributeList
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Eav\Model\AttributeFactory $attributeFactory,
        array $multipleAttributeList = []
    ) {
        parent::__construct($context, $coreRegistry, $resultPageFactory);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->multipleAttributeList = $multipleAttributeList;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $response = $this->dataObjectFactory->create();
        $response->setData('error', false);

        $attributeCode = $this->getRequest()->getParam('attribute_code');
        $frontendLabel = $this->getRequest()->getParam('frontend_label');
        $attributeCode = $attributeCode ?: $this->generateCode($frontendLabel[0]);
        $attributeId = $this->getRequest()->getParam('attribute_id');

        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        $attribute = $this->attributeFactory
            ->createAttribute(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->loadByCode($this->entityTypeId, $attributeCode);

        if ($attribute->getId() && !$attributeId) {
            $message = strlen($this->getRequest()->getParam('attribute_code'))
                ? __('An attribute with this code already exists.')
                : __('An attribute with the same code (%1) already exists.', $attributeCode);

            $this->setMessageToResponse($response, [$message]);

            $response->setData('error', true);
            $response->setData('product_attribute', $attribute->toArray());
        }

        $multipleOption = $this->getRequest()->getParam('frontend_input');
        $multipleOption = null === $multipleOption ? 'select' : $multipleOption;
        if (isset($this->multipleAttributeList[$multipleOption]) && null !== $multipleOption) {
            $this->checkUniqueOption(
                $response,
                $this->getRequest()->getParam($this->multipleAttributeList[$multipleOption])
            );
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }

    /**
     * Throws Exception if not unique values into options.
     *
     * @param array $optionsValues
     * @param array $deletedOptions
     * @return bool
     */
    private function isUniqueAdminValues(array $optionsValues, array $deletedOptions)
    {
        $adminValues = [];
        foreach ($optionsValues as $optionKey => $values) {
            if (!(isset($deletedOptions[$optionKey]) and $deletedOptions[$optionKey] === '1')) {
                $adminValues[] = reset($values);
            }
        }
        $uniqueValues = array_unique($adminValues);

        return ($uniqueValues === $adminValues);
    }

    /**
     * Set message to response object.
     *
     * @param DataObject $response
     * @param string[] $messages
     * @return DataObject
     */
    private function setMessageToResponse($response, $messages)
    {
        $messageKey = $this->getRequest()->getParam('message_key', static::DEFAULT_MESSAGE_KEY);
        if ($messageKey === static::DEFAULT_MESSAGE_KEY) {
            $messages = reset($messages);
        }

        return $response->setData($messageKey, $messages);
    }

    /**
     * @param DataObject $response
     * @param array|null $options
     */
    private function checkUniqueOption(DataObject $response, array $options = null)
    {
        if (is_array($options) && !$this->isUniqueAdminValues($options['value'], $options['delete'])) {
            $this->setMessageToResponse($response, [__('The value of Admin must be unique.')]);
            $response->setData('error', true);
        }
    }
}
