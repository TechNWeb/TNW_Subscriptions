<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Product;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Api\CustomerProductHistoryManagementInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface as QuoteSession;

/**
 * Class Trial - data modifier so that customer which already bought the trial can`t have another one
 */
class Trial implements ModifierInterface
{
    /**
     * @var CustomerProductHistoryManagementInterface
     */
    private $customerProductHistoryManagement;

    /**
     * @var Context
     */
    private $formContext;

    /**
     * @var QuoteSession
     */
    private $quoteSession;

    /**
     * Trial constructor.
     * @param CustomerProductHistoryManagementInterface $customerProductHistoryManagement
     * @param Context $formContext
     * @param QuoteSession $quoteSession
     */
    public function __construct(
        CustomerProductHistoryManagementInterface $customerProductHistoryManagement,
        Context $formContext,
        QuoteSession $quoteSession
    ) {
        $this->quoteSession = $quoteSession;
        $this->formContext = $formContext;
        $this->customerProductHistoryManagement = $customerProductHistoryManagement;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        $productId = (int) $this->formContext->getRequest()->getParam('product_id');
        if ($productId) {
            $customerId = $this->quoteSession->getCustomerId();
            $quotes = $this->formContext->getSession()->getSubQuotes();
            if ($quotes) {
                foreach ($quotes as $quote) {
                    foreach ($quote->getAllVisibleItems() as $item) {
                        if ($item->getProduct()->getId() == $productId
                            && $item->getProduct()->getTnwSubscrTrialStatus()
                        ) {
                            $data = $this->removeTrialFromData($data);
                        }
                    }
                }
            }
            if ($customerId) {
                $customerProductHistoryList = $this
                    ->customerProductHistoryManagement->getUniqueProductsInSubscriptionsForCustomer($customerId);
                if (in_array($productId, $customerProductHistoryList, true)) {
                    $data = $this->removeTrialFromData($data);
                }
            }
        }
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    private function removeTrialFromData($data)
    {
        $data['new_subscription']['use_trial'] = 0;
        $data['new_subscription']['trial_period'] = '';
        return $data;
    }
}
