<?php

namespace TNW\Subscriptions\Plugin\Multishipping\Controller\Checkout;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;

class Addresses
{

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var ManagerInterface
     */
    private $managerInterface;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Context
     */
    private $context;

    public function __construct(
        ResultFactory $resultFactory,
        ManagerInterface $managerInterface,
        UrlInterface $urlBuilder,
        Context $context
    ) {
        $this->managerInterface = $managerInterface;
        $this->resultFactory = $resultFactory;
        $this->urlBuilder = $urlBuilder;
        $this->context = $context;
    }

    public function afterExecute(\Magento\Multishipping\Controller\Checkout\Addresses $subject)
    {
        $resultRedirect = $this->resultFactory->create(
            ResultFactory::TYPE_REDIRECT
        );

        $this->managerInterface->addWarningMessage( __('Checkout with multiple addresses is not supported.') );
        return $resultRedirect->setUrl($this->urlBuilder->getUrl('checkout/cart', ['_secure' => true]));

    }
}
