<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Cron;

use Magento\Cron\Model\Schedule;
use Magento\Sales\Cron\CleanExpiredQuotes as Cron;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;

/**
 * Plugin to rewrite magento cron.
 */
class CleanExpiredQuotes extends Cron
{
    /**
     * Clean expired quotes (cron process)
     *
     * @param  $object
     * @param \Closure $proceed
     * @param Schedule $schedule
     */
    public function aroundExecute(
        Cron $object,
        \Closure $proceed,
        Schedule $schedule
    ) {
        $lifetimes = $this->storesConfig->getStoresConfigByPath('checkout/cart/delete_quote_after');
        foreach ($lifetimes as $storeId => $lifetime) {
            $lifetime *= self::LIFETIME;

            /** @var $quotes \Magento\Quote\Model\ResourceModel\Quote\Collection */
            $quotes = $this->quoteCollectionFactory->create();
            $quotes->getSelect()->joinLeft(
                ['sub_quotes' => SubscriptionProfileOrderInterface::MAIN_TABLE],
                'main_table.entity_id != sub_quotes.' . SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID,
                []
            );
            $quotes->addFieldToFilter(SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID, ['null' => true]);
            $quotes->addFieldToFilter('store_id', $storeId);
            $quotes->addFieldToFilter('updated_at', ['to' => date("Y-m-d", time() - $lifetime)]);
            $quotes->addFieldToFilter('is_active', 0);

            foreach ($this->getExpireQuotesAdditionalFilterFields() as $field => $condition) {
                $quotes->addFieldToFilter($field, $condition);
            }

            $quotes->walk('delete');
        }
    }
}