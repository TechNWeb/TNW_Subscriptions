<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller for Cart Subscription.
 */
class Index extends Action
{
    /**
     * Result page factory.
     *
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var \TNW\Subscriptions\Model\QuoteSessionInterface
     */
    private $quoteSession;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Customer\Model\Session\Proxy $customerSession
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->quoteSession = $quoteSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!$this->quoteSession->getSubQuoteItemsCount()) {
            return $this->resultRedirectFactory->create()->setPath('tnw_subscriptions/cart');
        }

        if (!$this->customerSession->isLoggedIn()) {
            foreach ($this->quoteSession->getSubQuotes() as $subQuote) {
                if ($this->checkoutHelper->isAllowedGuestCheckout($subQuote)) {
                    continue;
                }

                $this->messageManager->addErrorMessage(__('Guest checkout is disabled.'));
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Checkout'));

        return $resultPage;
    }
}
