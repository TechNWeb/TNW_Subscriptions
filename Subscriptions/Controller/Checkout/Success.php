<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Checkout;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    private $checkoutSession;

    /**
     * @var \Magento\Checkout\Model\Session\SuccessValidator
     */
    private $successValidator;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Checkout\Model\Session\SuccessValidator $successValidator
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->successValidator = $successValidator;
    }

    /**
     * Order success action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->successValidator->isValid()) {
            return $this->resultRedirectFactory->create()
                ->setPath('tnw_subscriptions/checkout');
        }

        //$this->checkoutSession->clearQuote();
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
    }
}