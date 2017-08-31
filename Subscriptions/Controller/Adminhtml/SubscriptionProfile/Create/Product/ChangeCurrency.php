<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create\Product;

use Magento\Ui\Controller\Adminhtml\Index\Render;
use TNW\Subscriptions\Model\Backend\Session\Quote as SessionQuote;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

/**
 * Class changes currency on selecting new currency.
 */
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
     * Return admin session object.
     *
     * @return SessionQuote
     */
    private function getQuoteSession()
    {
        return $this->_objectManager->get(SessionQuote::class);
    }

    /**
     * Return subscription create model.
     *
     * @return Create
     */
    private function getSubCreateModel()
    {
        return $this->_objectManager->get(Create::class);
    }
}
