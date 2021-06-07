<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\CustomerData;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Wishlist\CustomerData\Wishlist as OrigWishlist;
use Magento\Wishlist\Helper\Data as WishlistHelper;
use TNW\Subscriptions\Block\Product\ListProduct;
use TNW\Subscriptions\Block\Product\ListProduct\ListProductButtons;

/**
 * Plugin is used to add subscription data to wishlist items, stored in customer data
 */
class Wishlist
{
    /**
     * @var WishlistHelper
     */
    protected $wishlistHelper;

    /**
     * @var ListProduct
     */
    private $listProduct;

    /**
     * @var ListProductButtons
     */
    private $listProductButtons;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Wishlist constructor.
     * @param WishlistHelper $wishlistHelper
     * @param ListProduct $listProduct
     * @param ListProductButtons $listProductButtons
     * @param SerializerInterface $serializer
     */
    public function __construct(
        WishlistHelper $wishlistHelper,
        ListProduct $listProduct,
        ListProductButtons $listProductButtons,
        SerializerInterface $serializer
    ) {
        $this->wishlistHelper = $wishlistHelper;
        $this->listProduct = $listProduct;
        $this->listProductButtons = $listProductButtons;
        $this->serializer = $serializer;
    }

    /**
     * Add subscription parameters to wishlist item object.
     * @param OrigWishlist $subject
     * @param callable $proceed
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundGetSectionData(OrigWishlist $subject, callable $proceed)
    {
        $result = $proceed();
        if (!$result['counter']) {
            return $result;
        }
        $subsData = [];
        foreach ($this->wishlistHelper->getWishlistItemCollection() as $item) {
            $product = $item->getProduct();
            $productId = $item->getProductId();
            $subsData[$productId]['subs_top_message'] = $this->listProduct->getTopMessage($product);
            if ($this->listProduct->isAllowedProductType($product)
                && $this->listProduct->isSubscriptionPrice($product)) {
                $subsData[$productId]['subs_trial_price']
                    = $this->listProduct->getTrialPriceForCategory($product);
            } else {
                $subsData[$productId]['subs_trial_price'] = false;
            }
            $postParams = $this->serializer->unserialize($this->wishlistHelper->getAddToCartParams($item));
            $this->listProductButtons->addData([
                'product' => $product,
                'pos' => null,
                'view_mode' => 'grid',
                'position' => '',
                'post_params' => $postParams
            ]);
            $subsData[$productId]['subs_addtocart_params']
                = $this->listProductButtons->getCurrentSubsDataPostParams();
            $subsData[$productId]['is_subscribe']
                = $this->listProductButtons->isSubscribeAvailable($product);
            $subsData[$productId]['is_subscribe_only']
                = $this->listProductButtons->isOnlySubscribePurchase($product);
            $subsData[$productId]['is_one_time_purchase']
                = $this->listProductButtons->isOneTimePurchase($product);

        }
        foreach ($result['items'] as &$element) {
            $subsItem = array_filter($subsData, function ($value, $key) use ($element) {
                return $key == $element['product_id'];
            }, ARRAY_FILTER_USE_BOTH);
            $element += reset($subsItem);
        }
        return $result;
    }
}
