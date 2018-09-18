<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Checkout;

use Magento\Framework\App\Action\Action;

/**
 * Controller for Cart Subscription.
 */
class Index extends Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Customer\Model\Session\Proxy $customerSession
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $quote = $this->checkoutHelper->getQuote();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $this->resultRedirectFactory->create()->setPath('tnw_subscriptions/cart');
        }

        if (!$this->customerSession->isLoggedIn() && !$this->checkoutHelper->isAllowedGuestCheckout($quote)) {
            $this->messageManager->addErrorMessage(__('Guest checkout is disabled.'));
            return $this->resultRedirectFactory->create()->setPath('tnw_subscriptions/cart');
        }

        $this->customerSession->regenerateId();
        $this->checkoutSession->setCartWasUpdated(false);

        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Checkout'));

        return $resultPage;
    }
}
