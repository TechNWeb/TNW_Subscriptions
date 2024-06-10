<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct;

use Laminas\Validator\Regex;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Catalog\Model\Product\Url;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;

/**
 * Base controller class for group of profile product attribute controllers.
 */
abstract class Attribute extends Action
{
    /**
     * Base resource for group of profile product attribute controllers.
     */
    const ADMIN_RESOURCE = 'TNW_Subscriptions::ProductSubscriptionProfile_attributes';

    /**
     * @var string
     */
    protected $entityTypeId;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function dispatch(RequestInterface $request)
    {
        $this->entityTypeId = $this->_objectManager->create(
            \Magento\Eav\Model\Entity::class
        )->setType(
            ProductSubscriptionProfile::ENTITY
        )->getTypeId();

        return parent::dispatch($request);
    }

    /**
     * @param Phrase|null $title
     * @return Page
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function createActionPage($title = null)
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Product Subscription Profile Attributes'));

        return $resultPage;
    }

    /**
     * Generate code from label.
     *
     * @param $label
     * @return bool|string
     * @throws \RuntimeException
     */
    protected function generateCode($label)
    {
        $code = substr(
            preg_replace(
                '/[^a-z_0-9]/',
                '_',
                $this->_objectManager->create(Url::class)->formatUrlKey($label)
            ),
            0,
            30
        );
        $validatorAttrCode = new Regex(['pattern' => '/^[a-z][a-z_0-9]{0,29}[a-z0-9]$/']);
        if (!$validatorAttrCode->isValid($code)) {
            $code = 'attr_' . ($code ?: substr(hash('sha512', time()), 0, 8));
        }

        return $code;
    }
}
