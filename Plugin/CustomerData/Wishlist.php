<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\CustomerData;

use Magento\Wishlist\Helper\Data as WishlistHelper;
use TNW\Subscriptions\Model\Config\Product\SubscriptionProductView;

/**
 * Class Wishlist plugin
 */
class Wishlist
{
    /**
     * Subscription Product View Config model.
     *
     * @var SubscriptionProductView
     */
    private $subscriptionProductViewConfig;

    /**
     * @var WishlistHelper
     */
    protected $wishlistHelper;

    /**
     * LastOrderedItems constructor.
     *
     * @param SubscriptionProductView $subscriptionProductView
     * @param WishlistHelper $wishlistHelper
     */
    public function __construct(
        SubscriptionProductView $subscriptionProductView,
        WishlistHelper $wishlistHelper
    ) {
        $this->subscriptionProductViewConfig = $subscriptionProductView;
        $this->wishlistHelper = $wishlistHelper;
    }

    /**
     * Add "is_subscribe" and "is_subscribe_and_addtocart" parameters to wishlist item object.
     *
     * @param \Magento\Wishlist\CustomerData\Wishlist $subject
     * @param \Closure $proceed
     * @return array
     */
    public function aroundGetSectionData(\Magento\Wishlist\CustomerData\Wishlist $subject, \Closure $proceed)
    {
        $result = $proceed();
        $collection = $this->wishlistHelper->getWishlistItemCollection();
        $productsPreset = [];
        foreach ($collection->getItems() as $item) {
            $productId = $item->getProductId();
            if ($productId && $this->subscriptionProductViewConfig->isSubscribeAvailableById($productId)) {
                $productsPreset[$productId] = $item->getProductName();
            }
        }
        $onlySubscribeData = $this->subscriptionProductViewConfig->isOnlySubscribePurchaseByIds($productsPreset);
        $subscribeAndAddtocartData = $this
            ->subscriptionProductViewConfig
            ->isOneTimeAndSubscribePurchaseByIds($productsPreset);
        foreach ($result['items'] as $key => &$element) {
            $rt = array_filter($productsPreset, function ($value) use ($element) {
                return $value == $element['product_name'];
            });
            $element['is_subscribe'] = false;
            $element['is_subscribe_and_addtocart'] = false;
            if (count($rt)) {
                $element['is_subscribe'] = $onlySubscribeData[key($rt)];
                $element['is_subscribe_and_addtocart'] = $subscribeAndAddtocartData[key($rt)];
            }
        }
        return $result;
    }
}
