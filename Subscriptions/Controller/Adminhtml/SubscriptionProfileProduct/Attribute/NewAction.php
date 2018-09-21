<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfileProduct\Attribute;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Profile product attribute new action controller.
 */
class NewAction extends \Magento\Catalog\Controller\Adminhtml\Product\Attribute
{
    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * NewAction constructor.
     *
     * @param Context $context
     * @param FrontendInterface $attributeLabelCache
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        FrontendInterface $attributeLabelCache,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context, $attributeLabelCache, $coreRegistry, $resultPageFactory);
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        return $this->resultForwardFactory->create()->forward('edit');
    }
}
