<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create\Product;

use Magento\Ui\Controller\Adminhtml\Index\Render;
use TNW\Subscriptions\Model\Backend\Session\Quote as SessionQuote;

class ChangeCurrency extends Render
{
    /**
     * Action for AJAX request
     *
     * @return void
     */
    public function execute()
    {
        $currencyId = $this->getRequest()->getParam('currency_id');
        if (isset($currencyId)) {
            /** @var SessionQuote $quoteSession */
            $quoteSession = $this->getQuoteSession();
            $quoteSession->setCurrencyId($currencyId);
            $this->getSubCreateModel()->setCurrency($currencyId);

            $subQuotes = $quoteSession->getSubQuotes();
            foreach ($subQuotes as $subQuote) {
                if (!$subQuote->getQuoteCurrencyCode() || $subQuote->getQuoteCurrencyCode() != $currencyId) {
                    $subQuote->setQuoteCurrencyCode($currencyId);
                    $subQuote->collectTotals();
                }
            }
        }

        parent::execute();
    }

    /**
     * @return \TNW\Subscriptions\Model\Backend\Session\Quote
     */
    private function getQuoteSession()
    {
        return $this->_objectManager->get('TNW\Subscriptions\Model\Backend\Session\Quote');
    }

    /**
     * @return \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create
     */
    private function getSubCreateModel()
    {
        return $this->_objectManager->get('TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create');
    }
}
