<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\Product\Listing\Collector;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductRenderExtensionFactory;
use Magento\Catalog\Api\Data\ProductRenderInterface;
use Magento\Catalog\Ui\DataProvider\Product\ProductRenderCollectorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use TNW\Subscriptions\Block\Product\ListProduct;
use TNW\Subscriptions\Block\Product\ListProduct\ListProductButtons;

/**
 * Collector, which adds subscription data to product data storage on storefront
 */
class SubscriptionInfo implements ProductRenderCollectorInterface
{
    /**
     * @var ProductRenderExtensionFactory
     */
    private $productRenderExtensionFactory;

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
     * SubscriptionInfo constructor.
     * @param ProductRenderExtensionFactory $productRenderExtensionFactory
     * @param ListProduct $listProduct
     * @param ListProductButtons $listProductButtons
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ProductRenderExtensionFactory $productRenderExtensionFactory,
        ListProduct $listProduct,
        ListProductButtons $listProductButtons,
        SerializerInterface $serializer
    ) {
        $this->productRenderExtensionFactory = $productRenderExtensionFactory;
        $this->listProduct = $listProduct;
        $this->listProductButtons = $listProductButtons;
        $this->serializer = $serializer;
    }

    /**
     * @param ProductInterface $product
     * @param ProductRenderInterface $productRender
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function collect(ProductInterface $product, ProductRenderInterface $productRender)
    {
        $extensionAttributes = $productRender->getExtensionAttributes();

        if (!$extensionAttributes) {
            $extensionAttributes = $this->productRenderExtensionFactory->create();
        }
        $extensionAttributes->setSubsTopMessage($this->listProduct->getTopMessage($product));
        if ($this->listProduct->isAllowedProductType($product)
            && $this->listProduct->isSubscriptionPrice($product)) {
            $extensionAttributes->setSubsTrialPrice($this->listProduct->getTrialPriceForCategory($product));
        }
        $postParams = $this->serializer->unserialize($productRender->getAddToCartButton()->getPostData());
        $this->listProductButtons->addData([
            'product' => $product,
            'pos' => null,
            'view_mode' => 'grid',
            'position' => '',
            'post_params' => $postParams
        ]);

        $extensionAttributes->setSubsAddtocartParams($this->listProductButtons->getCurrentSubsDataPostParams());
        $extensionAttributes->setIsSubscribe($this->listProductButtons->isSubscribeAvailable($product));
        $extensionAttributes->setIsSubscribeOnly($this->listProductButtons->isOnlySubscribePurchase($product));
        $extensionAttributes->setIsOneTimePurchase($this->listProductButtons->isOneTimePurchase($product));
        $productRender->setExtensionAttributes($extensionAttributes);
    }
}
