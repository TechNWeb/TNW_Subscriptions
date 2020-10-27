<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Ui;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Controller\Adminhtml\AbstractAction;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Controller\UiActionInterface;

/**
 * Class renderer ui components.
 */
class Render extends AbstractAction implements UiActionInterface
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var Session
     */
    private $customerSession;

    public function __construct(
        Context $context,
        UiComponentFactory $factory,
        PageFactory $pageFactory,
        Session $customerSession
    ) {
        $this->pageFactory = $pageFactory;
        $this->customerSession = $customerSession;
        parent::__construct($context, $factory);
    }

    /**
     * Action for AJAX request
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if ($this->_request->getParam('namespace') === null) {
            $this->_redirect('admin/noroute');
            return;
        }

        $component = $this->factory->create($this->_request->getParam('namespace'));
        $this->prepareComponent($component);
        $this->_response->appendBody((string) $component->render());
    }

    /**
     * Call prepare method in the component UI
     *
     * @param UiComponentInterface $component
     * @return void
     */
    protected function prepareComponent(UiComponentInterface $component)
    {
        foreach ($component->getChildComponents() as $child) {
            $this->prepareComponent($child);
        }
        $component->prepare();
    }
}
