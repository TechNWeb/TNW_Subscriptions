<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Quote;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector as MagentoCollector;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;

/**
 * Plugin for tax collector model.
 */
class CommonTaxCollector
{
    /**
     * Adds subscription data into quote item tax details object.
     *
     * @param MagentoCollector $subject
     * @param \Closure $proceed
     * @param QuoteDetailsItemInterfaceFactory $itemDataObjectFactory
     * @param AbstractItem $item
     * @param bool $priceIncludesTax
     * @param bool $useBaseCurrency
     * @param string $parentCode
     * @return QuoteDetailsItemInterface
     */
    public function aroundMapItem(
        MagentoCollector $subject,
        \Closure $proceed,
        QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        AbstractItem $item,
        $priceIncludesTax,
        $useBaseCurrency,
        $parentCode = null
    ) {
        /** @var QuoteDetailsItemInterface $result */
        $result = $proceed($itemDataObjectFactory,
            $item,
            $priceIncludesTax,
            $useBaseCurrency,
            $parentCode
        );
        $subsData = $item->getBuyRequest()->getData(Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME) ?: [];
        if ($subsData) {
            $presetPrice = $subsData[Create::NON_UNIQUE]['current_preset_qty_price'];
            $usePresetQty = $subsData[Create::UNIQUE]['use_preset_qty'];
            $result->setData('subscription_use_preset_qty', $usePresetQty);
            $result->setData('subscription_preset_qty_price', $presetPrice);
            $result->setData('store_id', $item->getQuote()->getStoreId());
        }

        return $result;
    }
}
