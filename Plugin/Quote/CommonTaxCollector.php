<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Quote;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector as MagentoCollector;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use Magento\Framework\Serialize\SerializerInterface;


/**
 * Plugin for tax collector model.
 */
class CommonTaxCollector
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

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
        $result = $proceed(
            $itemDataObjectFactory,
            $item,
            $priceIncludesTax,
            $useBaseCurrency,
            $parentCode
        );

        $option = $item->getOptionByCode('info_buyRequest');
        if (null === $option) {
            return $result;
        }

        $infoBuyRequest = $option ? $this->unserialize($option->getValue()) : [];
        if (empty($infoBuyRequest[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME])) {
            return $result;
        }

        $subsData = $infoBuyRequest[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME];
        if (empty($subsData)) {
            return $result;
        }

        $presetPrice = !empty($subsData[Create::NON_UNIQUE]['current_preset_qty_price'])
            ? $subsData[Create::NON_UNIQUE]['current_preset_qty_price']
            : 0;
        $usePresetQty =  !empty($subsData[Create::UNIQUE]['use_preset_qty'])
            ? $subsData[Create::UNIQUE]['use_preset_qty']
            : 0;

        if ($presetPrice && $usePresetQty) {
            $result->setData('subscription_use_preset_qty', $usePresetQty);
            $result->setData('subscription_preset_qty_price', $presetPrice);
            $result->setData('store_id', $item->getQuote()->getStoreId());
        }

        return $result;
    }

    /**
     * @param $string
     * @return string|int|float|bool|array|null
     */
    private function unserialize($string)
    {
        $result = json_decode($string, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $result = $this->serializer->unserialize($string);
        }

        return $result;
    }
}
