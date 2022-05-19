<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model\Quote\Item;

use Magento\Bundle\Model\Product\Type as TypeBundle;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product;

/**
 * Class Processor - plugin to add additional logic for Magento\Quote\Model\Quote\Item\Processor::prepare method
 */
class Processor
{
    /**
     * @var Product
     */
    private $productModifier;

    /**
     * Processor constructor.
     * @param Product $productModifier
     */
    public function __construct(
        Product $productModifier
    ) {
        $this->productModifier = $productModifier;
    }

    /**
     * @param Item\Processor $subject
     * @param callable $callback
     * @param Item $item
     * @param DataObject $request
     * @param ProductModel $candidate
     */
    public function aroundPrepare(
        Item\Processor $subject,
        callable $callback,
        Item $item,
        DataObject $request,
        ProductModel $candidate
    ) {
        $callback($item, $request, $candidate);

        $buyRequest = $item->getBuyRequest();
        if (isset($buyRequest['subscribe_active']) && $buyRequest['subscribe_active']) {
            $this->productModifier->reset();
            $this->productModifier->setData($buyRequest->getData());
            $this->productModifier->setProduct($candidate);

            // Set initial fee to item, except bundle children
            if (!isset($request['bundle_option'])
                || $candidate->getTypeId() === TypeBundle::TYPE_CODE
            ) {
                $this->productModifier->setInitialFeeToItem($item);
            }

            // In case of grouped product child items, we need to set custom price from their buyRequest
            if (isset($request['subs_group'])) {
                $customPrice = $buyRequest->getCustomPrice();
                if (!empty($customPrice) && !$item->getCustomPrice()) {
                    $item->setCustomPrice($customPrice);
                    $item->setOriginalCustomPrice($customPrice);
                }
            }
        }
    }
}
