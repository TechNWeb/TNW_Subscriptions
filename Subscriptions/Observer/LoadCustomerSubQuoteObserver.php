<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use TNW\Subscriptions\Model\CustomerQuote\Manager as CustomerQuoteManager;
use TNW\Subscriptions\Model\Session\Quote as QuoteSession;

/**
 * Class LoadCustomerSubQuoteObserver
 */
class LoadCustomerSubQuoteObserver implements ObserverInterface
{
    /**
     * Customer quote manager
     *
     * @var CustomerQuoteManager
     */
    protected $customerQuoteManager;

    /**
     * Message Manager
     *
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * Constructor for LoadCustomerSubQuoteObserver
     *
     * @param CustomerQuoteManager $customerQuoteManager
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        CustomerQuoteManager $customerQuoteManager,
        ManagerInterface $messageManager
    ) {
        $this->customerQuoteManager = $customerQuoteManager;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $this->customerQuoteManager->loadCustomerSubQuote();
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Load customer sub quote error'));
        }
    }
}
